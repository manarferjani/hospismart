<?php

namespace App\Service;

class AiImageService
{
    private string $uploadDir;

    public function __construct(string $projectDir)
    {
        $this->uploadDir = $projectDir . '/public/uploads/medicaments';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Generate a professional AI image for a medication using multiple providers.
     * Order of attempt: Hugging Face (HQ) -> Pollinations.ai (Fast) -> Wiki Commons (Real)
     */
    public function generateImage(string $medicamentName): ?string
    {
        set_time_limit(0);
        error_log("AiImageService: Starting generation for '{$medicamentName}'");

        // 1. Try Hugging Face (High Quality)
        $imageData = $this->tryHuggingFace($medicamentName);
        if ($imageData) {
            return $this->saveImage($imageData['data'], $medicamentName, $imageData['ext']);
        }

        // 2. Try Pollinations.ai (Fast Fallback)
        error_log("AiImageService: Falling back to Pollinations.ai");
        $imageData = $this->tryPollinations($medicamentName);
        if ($imageData) {
            return $this->saveImage($imageData['data'], $medicamentName, $imageData['ext']);
        }

        // 3. Try Wikipedia Commons (Real image search)
        error_log("AiImageService: Falling back to Wikipedia Commons");
        $imageData = $this->tryWikiCommons($medicamentName);
        if ($imageData) {
            return $this->saveImage($imageData['data'], $medicamentName, $imageData['ext']);
        }

        error_log("AiImageService: FAILURE - All providers failed for '{$medicamentName}'");
        return null;
    }

    private function tryHuggingFace(string $name): ?array
    {
        $apiToken = $_ENV['HF_API_TOKEN'] ?? $_SERVER['HF_API_TOKEN'] ?? null;
        if (!$apiToken) return null;

        $prompt = sprintf('professional pharmaceutical product photo of %s medication, pill bottle on white background, studio lighting, high quality', $name);
        $payload = json_encode(['inputs' => $prompt]);
        
        $models = [
            'stabilityai/stable-diffusion-xl-base-1.0',
            'runwayml/stable-diffusion-v1-5',
        ];

        foreach ($models as $model) {
            $ch = curl_init('https://router.huggingface.co/hf-inference/models/' . $model);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiToken, 'Content-Type: application/json'],
                CURLOPT_TIMEOUT => 25, // Lower timeout for web responsiveness
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            if ($httpCode === 200 && str_contains((string)$type, 'image') && strlen((string)$response) > 5000) {
                error_log("AiImageService: HF SUCCESS ($model)");
                return ['data' => $response, 'ext' => str_contains($type, 'png') ? 'png' : 'jpg'];
            }
        }
        return null;
    }

    private function tryPollinations(string $name): ?array
    {
        $prompt = urlencode("pharmaceutical {$name} medication bottle white background");
        $seed = mt_rand(1, 9999);
        $url = "https://image.pollinations.ai/prompt/{$prompt}?width=512&height=512&nologo=true&seed={$seed}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && strlen((string)$data) > 5000) {
            error_log("AiImageService: Pollinations SUCCESS");
            return ['data' => $data, 'ext' => 'jpg'];
        }
        return null;
    }

    private function tryWikiCommons(string $name): ?array
    {
        $query = urlencode($name . ' medicine');
        $url = "https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrsearch={$query}&gsrnamespace=6&gsrlimit=1&prop=imageinfo&iiprop=url&iiurlwidth=512&format=json";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'HospiSmart/1.0');
        $resp = curl_exec($ch);
        curl_close($ch);

        $json = json_decode((string)$resp, true);
        if (isset($json['query']['pages'])) {
            $page = reset($json['query']['pages']);
            if (isset($page['imageinfo'][0]['thumburl'])) {
                $imgUrl = $page['imageinfo'][0]['thumburl'];
                $imgData = file_get_contents($imgUrl);
                if ($imgData && strlen($imgData) > 1000) {
                    error_log("AiImageService: Wiki SUCCESS");
                    return ['data' => $imgData, 'ext' => 'jpg'];
                }
            }
        }
        return null;
    }

    private function saveImage(string $data, string $name, string $ext): ?string
    {
        $safeName = substr(preg_replace('/[^a-z0-9]/', '_', strtolower($name)), 0, 50);
        $filename = 'med_' . uniqid() . '_' . $safeName . '.' . $ext;
        $filepath = $this->uploadDir . '/' . $filename;

        if (file_put_contents($filepath, $data)) {
            return $filename;
        }
        return null;
    }

    /**
     * Delete an existing image file
     */
    public function deleteImage(string $filename): void
    {
        $filepath = $this->uploadDir . '/' . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
}
