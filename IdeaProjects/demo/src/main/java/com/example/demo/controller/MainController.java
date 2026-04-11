package com.example.demo.controller;

import com.example.demo.HelloApplication;


import com.example.demo.domain.Reclamation;
import com.example.demo.service.DatabaseService;
import javafx.event.ActionEvent;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.concurrent.Task;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Node;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.chart.PieChart;
import javafx.scene.control.Alert;
import javafx.scene.control.Label;
import javafx.scene.control.ListView;
import javafx.scene.control.TextArea;
import javafx.stage.Stage;

import java.io.IOException;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.List;
import java.util.Objects;

public class MainController {
    @FXML
    private Label statusLabel;

    @FXML
    private Label totalReclamationsLabel;
    @FXML
    private Label totalReponsesLabel;
    @FXML
    private Label tauxReponseLabel;
    @FXML
    private PieChart reponsePieChart;
    @FXML
    private ListView<String> activityListView;
    @FXML
    private TextArea systemInfoArea;

    private final DatabaseService databaseService = new DatabaseService();

    @FXML
    public void initialize() {
        updateConnectionStatus();
        refreshDashboard();
    }

    @FXML
    public void openFrontOffice(ActionEvent event) {
        try {
            switchScene(event, "frontoffice-view.fxml", "HospiSmart - Front Office");
        } catch (IOException e) {
            showNavigationError("Front Office", e);
        }
    }

    @FXML
    public void openPortal(ActionEvent event) {
        try {
            switchScene(event, "portal-view.fxml", "HospiSmart - Portail");
        } catch (IOException e) {
            showNavigationError("Portail", e);
        }
    }

    private void switchScene(ActionEvent event, String fxml, String title) throws IOException {
        FXMLLoader loader = new FXMLLoader(HelloApplication.class.getResource("view/" + fxml));
        Parent root = loader.load();

        Scene scene = new Scene(root, 1500, 950);
        scene.getStylesheets().add(Objects.requireNonNull(
            HelloApplication.class.getResource("app.css"),
            "app.css introuvable"
        ).toExternalForm());

        Stage stage = (Stage) ((Node) event.getSource()).getScene().getWindow();
        stage.setTitle(title);
        stage.setScene(scene);
        stage.show();
    }

    private void showNavigationError(String target, Exception e) {
        Alert alert = new Alert(Alert.AlertType.ERROR);
        alert.setTitle("Navigation impossible");
        alert.setHeaderText("Ouverture de " + target + " échouée");
        alert.setContentText(e.getMessage());
        alert.showAndWait();
    }

    @FXML
    public void refreshDashboard() {
        Task<DashboardSnapshot> task = new Task<>() {
            @Override
            protected DashboardSnapshot call() {
                List<Reclamation> reclamations = databaseService.loadAllReclamations();

                // Compter les réponses totales
                int totalReponses = 0;
                int reclamationsAvecReponse = 0;
                ObservableList<String> activityItems = FXCollections.observableArrayList();

                for (Reclamation rec : reclamations) {
                    int reponseCount = databaseService.countReponses(rec.getId());
                    totalReponses += reponseCount;
                    if (reponseCount > 0) {
                        reclamationsAvecReponse++;
                    }
                }

                int limit = Math.min(5, reclamations.size());
                for (int i = 0; i < limit; i++) {
                    Reclamation rec = reclamations.get(i);
                    int count = databaseService.countReponses(rec.getId());
                    activityItems.add(String.format("%s • %s • %s réponse(s)",
                        rec.getTitre(), rec.getStatut(), count));
                }

                double tauxReponse = reclamations.size() > 0 ?
                    (reclamationsAvecReponse * 100.0 / reclamations.size()) : 0;

                return new DashboardSnapshot(reclamations.size(), totalReponses, reclamationsAvecReponse, tauxReponse, activityItems);
            }
        };

        task.setOnSucceeded(event -> {
            DashboardSnapshot snapshot = task.getValue();
            totalReclamationsLabel.setText(String.valueOf(snapshot.totalReclamations));
            totalReponsesLabel.setText(String.valueOf(snapshot.totalReponses));
            tauxReponseLabel.setText(String.format("%.1f%%", snapshot.tauxReponse));
            reponsePieChart.setData(FXCollections.observableArrayList(
                new PieChart.Data("Avec réponse", snapshot.reclamationsAvecReponse),
                new PieChart.Data("Sans réponse", Math.max(0, snapshot.totalReclamations - snapshot.reclamationsAvecReponse))
            ));
            reponsePieChart.setLegendVisible(true);
            activityListView.setItems(snapshot.activityItems);
            systemInfoArea.setText(buildSystemInfo(snapshot.totalReclamations, snapshot.totalReponses, snapshot.reclamationsAvecReponse));
        });

        task.setOnFailed(event -> systemInfoArea.setText("Impossible de charger les statistiques du dashboard."));

        new Thread(task, "dashboard-updater").start();
    }

    private void updateConnectionStatus() {
        boolean isConnected = databaseService.isDatabaseReachable();
        String status = isConnected ? "✓ Connecté à hospismart" : "✗ Déconnecté";
        String color = isConnected ? "#86efac" : "#fecaca";
        statusLabel.setText(status);
        statusLabel.setStyle("-fx-text-fill: " + color + ";");
    }

    private String buildSystemInfo(int totalReclamations, int totalReponses, int reclamationsAvecReponse) {
        DateTimeFormatter formatter = DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm:ss");

        return String.format(
            "=== INFORMATIONS SYSTÈME ===\n" +
            "Date/Heure: %s\n" +
            "Base de données: hospismart\n" +
            "Serveur: %s\n\n" +
            "=== STATISTIQUES ===\n" +
            "Réclamations totales: %d\n" +
            "Réclamations avec réponse: %d\n" +
            "Réclamations sans réponse: %d\n" +
            "Réponses totales: %d\n" +
            "Taux de réponse: %.1f%%\n\n" +
            "=== ARCHITECTURE ===\n" +
            "Front Office: Création et gestion des réclamations\n" +
            "Back Office: Réponses et suivi des réclamations\n" +
            "Synchronisation: Temps réel avec MySQL",
            LocalDateTime.now().format(formatter),
            databaseService.getDatabaseConfig().getUrl().split("/")[2],
            totalReclamations,
            reclamationsAvecReponse,
            totalReclamations - reclamationsAvecReponse,
            totalReponses,
            totalReclamations > 0 ? (reclamationsAvecReponse * 100.0 / totalReclamations) : 0
        );
    }

    private static final class DashboardSnapshot {
        private final int totalReclamations;
        private final int totalReponses;
        private final int reclamationsAvecReponse;
        private final double tauxReponse;
        private final ObservableList<String> activityItems;

        private DashboardSnapshot(int totalReclamations, int totalReponses, int reclamationsAvecReponse,
                                  double tauxReponse, ObservableList<String> activityItems) {
            this.totalReclamations = totalReclamations;
            this.totalReponses = totalReponses;
            this.reclamationsAvecReponse = reclamationsAvecReponse;
            this.tauxReponse = tauxReponse;
            this.activityItems = activityItems;
        }
    }
}








