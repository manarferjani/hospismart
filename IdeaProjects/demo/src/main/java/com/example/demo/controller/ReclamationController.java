package com.example.demo.controller;

import com.example.demo.domain.Reclamation;
import com.example.demo.service.DatabaseService;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.concurrent.Task;
import javafx.fxml.FXML;
import javafx.scene.control.Label;
import javafx.scene.control.TableColumn;
import javafx.scene.control.TableView;
import javafx.scene.control.cell.PropertyValueFactory;

import java.time.format.DateTimeFormatter;
import java.util.List;

public class ReclamationController {
    @FXML
    private TableView<Reclamation> reclamationTable;

    @FXML
    private TableColumn<Reclamation, Integer> idColumn;

    @FXML
    private TableColumn<Reclamation, String> titreColumn;

    @FXML
    private TableColumn<Reclamation, String> patientColumn;

    @FXML
    private TableColumn<Reclamation, String> statutColumn;

    @FXML
    private TableColumn<Reclamation, String> categorieColumn;

    @FXML
    private TableColumn<Reclamation, String> prioriteColumn;

    @FXML
    private TableColumn<Reclamation, String> emailColumn;

    @FXML
    private TableColumn<Reclamation, String> dateColumn;

    @FXML
    private Label statusLabel;

    private final DatabaseService databaseService = new DatabaseService();

    @FXML
    public void initialize() {
        setupColumns();
        loadReclamations();
    }

    private void setupColumns() {
        idColumn.setCellValueFactory(new PropertyValueFactory<>("id"));
        titreColumn.setCellValueFactory(new PropertyValueFactory<>("titre"));
        patientColumn.setCellValueFactory(new PropertyValueFactory<>("nomPatient"));
        statutColumn.setCellValueFactory(new PropertyValueFactory<>("statut"));
        categorieColumn.setCellValueFactory(new PropertyValueFactory<>("categorie"));
        prioriteColumn.setCellValueFactory(new PropertyValueFactory<>("priorite"));
        emailColumn.setCellValueFactory(new PropertyValueFactory<>("email"));
        dateColumn.setCellValueFactory(cellData -> {
            Reclamation reclamation = cellData.getValue();
            if (reclamation.getDateCreation() != null) {
                DateTimeFormatter formatter = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm");
                return new javafx.beans.property.SimpleStringProperty(
                    reclamation.getDateCreation().format(formatter)
                );
            }
            return new javafx.beans.property.SimpleStringProperty("");
        });
    }

    private void loadReclamations() {
        statusLabel.setText("Chargement des réclamations...");

        Task<List<Reclamation>> loadTask = new Task<>() {
            @Override
            protected List<Reclamation> call() {
                return databaseService.loadAllReclamations();
            }
        };

        loadTask.setOnSucceeded(event -> {
            List<Reclamation> reclamations = loadTask.getValue();
            ObservableList<Reclamation> observableList = FXCollections.observableArrayList(reclamations);
            reclamationTable.setItems(observableList);
            statusLabel.setText("Réclamations chargées: " + reclamations.size() + " résultats");
        });

        loadTask.setOnFailed(event -> {
            statusLabel.setText("Erreur lors du chargement des réclamations");
            event.getSource().getException().printStackTrace();
        });

        Thread thread = new Thread(loadTask, "reclamation-loader");
        thread.setDaemon(true);
        thread.start();
    }
}


