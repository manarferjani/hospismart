package com.example.demo.controller;

import com.example.demo.service.DatabaseService;
import javafx.fxml.FXML;
import javafx.scene.control.Label;
import javafx.concurrent.Task;

public class HelloController {
    @FXML
    private Label welcomeText;

    private final DatabaseService databaseService = new DatabaseService();

    @FXML
    protected void onHelloButtonClick() {
        welcomeText.setText("Vérification de la base MySQL...");

        Task<Boolean> connectivityTask = new Task<>() {
            @Override
            protected Boolean call() {
                return databaseService.isDatabaseReachable();
            }
        };

        connectivityTask.setOnSucceeded(event -> {
            boolean reachable = Boolean.TRUE.equals(connectivityTask.getValue());
            welcomeText.setText(reachable ? "Connexion MySQL OK" : "Connexion MySQL indisponible");
        });

        connectivityTask.setOnFailed(event -> welcomeText.setText("Erreur lors de la vérification MySQL"));

        Thread thread = new Thread(connectivityTask, "mysql-connectivity-check");
        thread.setDaemon(true);
        thread.start();
    }
}
