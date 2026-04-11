import java.io.File;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.List;

public class Refactor {
    public static void main(String[] args) throws Exception {
        System.out.println("Starting refactoring...");

        // 1. Process all java files
        Files.walk(Paths.get("C:/Users/User/IdeaProjects/demo/src/main/java"))
            .filter(Files::isRegularFile)
            .filter(p -> p.toString().endsWith(".java"))
            .forEach(Refactor::processJavaFile);

        // 2. Process all fxml files
        Files.walk(Paths.get("C:/Users/User/IdeaProjects/demo/src/main/resources"))
            .filter(Files::isRegularFile)
            .filter(p -> p.toString().endsWith(".fxml"))
            .forEach(Refactor::processFxmlFile);

        System.out.println("Refactoring complete.");
    }

    private static void processJavaFile(Path path) {
        try {
            String content = new String(Files.readAllBytes(path));
            boolean changed = false;

            // Model Package
            if (content.contains("package com.example.demo.domain;")) {
                content = content.replace("package com.example.demo.domain;", "package com.example.demo.model;");
                changed = true;
            }
            if (content.contains("import com.example.demo.domain.")) {
                content = content.replace("import com.example.demo.domain.", "import com.example.demo.model.");
                changed = true;
            }

            // Controller Package
            if (path.toString().replace("\\", "/").contains("/controller/")) {
                if (content.contains("package com.example.demo;") && !content.contains("package com.example.demo.controller;")) {
                    content = content.replace("package com.example.demo;", "package com.example.demo.controller;");
                    changed = true;
                }
            }

            // Add controller import if not in controller and needs it
            if (!path.toString().replace("\\", "/").contains("/controller/") && content.contains("Controller")) {
                if (!content.contains("import com.example.demo.controller.")) {
                    content = content.replace("import com.example.demo.model.", "import com.example.demo.model.\nimport com.example.demo.controller.*;");
                    changed = true;
                }
            }

            if (changed) {
                Files.write(path, content.getBytes());
                System.out.println("Updated: " + path);
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private static void processFxmlFile(Path path) {
        try {
            String content = new String(Files.readAllBytes(path));
            String original = content;

            // This replace regex handles standard controllers
            content = content.replaceAll("fx:controller=\"com\\.example\\.demo\\.([a-zA-Z0-9_]+Controller)\"", "fx:controller=\"com.example.demo.controller.$1\"");

            // For ReclamationControllerCRUD specifically which might cause duplication if already done by regex
            // Wait, Java regex replaceAll works like this:

            if (!content.equals(original)) {
                Files.write(path, content.getBytes());
                System.out.println("Updated: " + path);
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}

