<?php

namespace App\Service;

use App\Entity\Evenement;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service to fetch weather forecasts from WeatherAPI.com
 */
class WeatherService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $weatherApiKey
        )
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $weatherApiKey;
    }

    /**
     * Get weather forecast for an event based on its coordinates and date.
     *
     * @return array{temp_c: float, condition_text: string, condition_icon: string}|null
     */
    public function getForecastForEvent(Evenement $event): ?array
    {
        $lat = $event->getLatitude();
        $lng = $event->getLongitude();
        $dateDebut = $event->getDateDebut();

        if ($lat === null || $lng === null || $dateDebut === null) {
            return null;
        }

        if (empty($this->apiKey) || $this->apiKey === 'YOUR_WEATHER_API_KEY_HERE') {
            return null;
        }

        $dateStr = $dateDebut->format('Y-m-d');

        try {
            $response = $this->httpClient->request('GET', 'https://api.weatherapi.com/v1/forecast.json', [
                'query' => [
                    'key' => $this->apiKey,
                    'q' => sprintf('%s,%s', $lat, $lng),
                    'dt' => $dateStr,
                ],
                'timeout' => 5,
            ]);

            $data = $response->toArray();

            if (!isset($data['forecast']['forecastday'][0]['day'])) {
                $this->logger->warning('WeatherAPI: unexpected response structure', ['event_id' => $event->getId()]);
                return null;
            }

            $day = $data['forecast']['forecastday'][0]['day'];

            return [
                'temp_c' => $day['avgtemp_c'] ?? null,
                'temp_min_c' => $day['mintemp_c'] ?? null,
                'temp_max_c' => $day['maxtemp_c'] ?? null,
                'condition_text' => $day['condition']['text'] ?? 'N/A',
                'condition_icon' => $day['condition']['icon'] ?? null,
                'humidity' => $day['avghumidity'] ?? null,
                'wind_kph' => $day['maxwind_kph'] ?? null,
                'chance_of_rain' => $day['daily_chance_of_rain'] ?? null,
            ];
        }
        catch (\Throwable $e) {
            $this->logger->error('WeatherAPI error: ' . $e->getMessage(), [
                'event_id' => $event->getId(),
                'exception' => $e,
            ]);
            return null;
        }
    }

    /**
     * Get weather forecasts for multiple events at once.
     *
     * @param Evenement[] $events
     * @return array<int, array|null> Indexed by event ID
     */
    public function getForecastsForEvents(array $events): array
    {
        $results = [];
        foreach ($events as $event) {
            $results[$event->getId()] = $this->getForecastForEvent($event);
        }
        return $results;
    }
}
