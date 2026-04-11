package com.example.demo.controller;

import com.example.demo.domain.Reclamation;
import com.example.demo.domain.Service;
import com.example.demo.domain.User;
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

public class HospiSmartController {
    @FXML
    private Label connectionStatusLabel;

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
    private Label userStatusLabel;

    @FXML
    private TableView<User> userTable;
    @FXML
    private TableColumn<User, Integer> userIdColumn;
    @FXML
    private TableColumn<User, String> userPrenomColumn;
    @FXML
    private TableColumn<User, String> userNomColumn;
    @FXML
    private TableColumn<User, String> userEmailColumn;
    @FXML
    private TableColumn<User, String> userTelephoneColumn;
    @FXML
    private TableColumn<User, String> userRoleColumn;
    @FXML
    private TableColumn<User, String> userStatutColumn;

    @FXML
    private TableView<Service> serviceTable;
    @FXML
    private TableColumn<Service, Integer> serviceIdColumn;
    @FXML
    private TableColumn<Service, String> serviceNomColumn;
    @FXML
    private TableColumn<Service, String> serviceDescriptionColumn;
    @FXML
    private TableColumn<Service, String> serviceResponsableColumn;
    @FXML
    private TableColumn<Service, Integer> serviceCapaciteColumn;

    @FXML
    private Label serviceStatusLabel;
    @FXML
    private Label totalReclamationsLabel;
    @FXML
    private Label totalUsersLabel;
    @FXML
    private Label totalServicesLabel;
    @FXML
    private Label databaseInfoLabel;

    private final DatabaseService databaseService = new DatabaseService();

    @FXML
    public void initialize() {
        setupReclamationColumns();
        setupUserColumns();
        setupServiceColumns();
        refreshData();
        updateConnectionStatus();
    }

    @FXML
    public void refreshData() {
        loadReclamations();
        loadUsers();
        loadServices();
        updateDashboard();
    }

    private void updateConnectionStatus() {
        boolean isConnected = databaseService.isDatabaseReachable();
        String status = isConnected ? "✓ Connecté à hospismart" : "✗ Déconnecté";
        String color = isConnected ? "#27ae60" : "#e74c3c";
        connectionStatusLabel.setText(status);
        connectionStatusLabel.setStyle("-fx-text-fill: " + color + ";");
    }

    private void setupReclamationColumns() {
        idColumn.setCellValueFactory(new PropertyValueFactory<>("id"));
        titreColumn.setCellValueFactory(new PropertyValueFactory<>("titre"));
        patientColumn.setCellValueFactory(new PropertyValueFactory<>("nomPatient"));
        statutColumn.setCellValueFactory(new PropertyValueFactory<>("statut"));
        categorieColumn.setCellValueFactory(new PropertyValueFactory<>("categorie"));
        prioriteColumn.setCellValueFactory(new PropertyValueFactory<>("priorite"));
        emailColumn.setCellValueFactory(new PropertyValueFactory<>("email"));
        dateColumn.setCellValueFactory(cellData -> {
            Reclamation r = cellData.getValue();
            if (r.getDateCreation() != null) {
                DateTimeFormatter formatter = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm");
                return new javafx.beans.property.SimpleStringProperty(
                    r.getDateCreation().format(formatter)
                );
            }
            return new javafx.beans.property.SimpleStringProperty("");
        });
    }

    private void setupUserColumns() {
        userIdColumn.setCellValueFactory(new PropertyValueFactory<>("id"));
        userPrenomColumn.setCellValueFactory(new PropertyValueFactory<>("prenom"));
        userNomColumn.setCellValueFactory(new PropertyValueFactory<>("nom"));
        userEmailColumn.setCellValueFactory(new PropertyValueFactory<>("email"));
        userTelephoneColumn.setCellValueFactory(new PropertyValueFactory<>("telephone"));
        userRoleColumn.setCellValueFactory(new PropertyValueFactory<>("role"));
        userStatutColumn.setCellValueFactory(new PropertyValueFactory<>("statut"));
    }

    private void setupServiceColumns() {
        serviceIdColumn.setCellValueFactory(new PropertyValueFactory<>("id"));
        serviceNomColumn.setCellValueFactory(new PropertyValueFactory<>("nom"));
        serviceDescriptionColumn.setCellValueFactory(new PropertyValueFactory<>("description"));
        serviceResponsableColumn.setCellValueFactory(new PropertyValueFactory<>("responsable"));
        serviceCapaciteColumn.setCellValueFactory(new PropertyValueFactory<>("capacite"));
    }

    private void loadReclamations() {
        userStatusLabel.setText("Chargement des réclamations...");

        Task<List<Reclamation>> task = new Task<>() {
            @Override
            protected List<Reclamation> call() {
                return databaseService.loadAllReclamations();
            }
        };

        task.setOnSucceeded(event -> {
            List<Reclamation> data = task.getValue();
            ObservableList<Reclamation> observableList = FXCollections.observableArrayList(data);
            reclamationTable.setItems(observableList);
            userStatusLabel.setText("Réclamations chargées: " + data.size() + " résultats");
        });

        task.setOnFailed(event -> userStatusLabel.setText("Erreur lors du chargement"));

        new Thread(task, "reclamation-loader").start();
    }

    private void loadUsers() {
        Task<List<User>> task = new Task<>() {
            @Override
            protected List<User> call() {
                return databaseService.loadAllUsers();
            }
        };

        task.setOnSucceeded(event -> {
            List<User> data = task.getValue();
            ObservableList<User> observableList = FXCollections.observableArrayList(data);
            userTable.setItems(observableList);
            userStatusLabel.setText("Utilisateurs chargés: " + data.size() + " résultats");
        });

        task.setOnFailed(event -> userStatusLabel.setText("Erreur lors du chargement"));

        new Thread(task, "user-loader").start();
    }

    private void loadServices() {
        Task<List<Service>> task = new Task<>() {
            @Override
            protected List<Service> call() {
                return databaseService.loadAllServices();
            }
        };

        task.setOnSucceeded(event -> {
            List<Service> data = task.getValue();
            ObservableList<Service> observableList = FXCollections.observableArrayList(data);
            serviceTable.setItems(observableList);
            serviceStatusLabel.setText("Services chargés: " + data.size() + " résultats");
        });

        task.setOnFailed(event -> serviceStatusLabel.setText("Erreur lors du chargement"));

        new Thread(task, "service-loader").start();
    }

    private void updateDashboard() {
        Task<Void> task = new Task<>() {
            @Override
            protected Void call() {
                List<Reclamation> reclamations = databaseService.loadAllReclamations();
                List<User> users = databaseService.loadAllUsers();
                List<Service> services = databaseService.loadAllServices();

                totalReclamationsLabel.setText(String.valueOf(reclamations.size()));
                totalUsersLabel.setText(String.valueOf(users.size()));
                totalServicesLabel.setText(String.valueOf(services.size()));

                databaseInfoLabel.setText("Base de données: hospismart | Serveur: " +
                    databaseService.getDatabaseConfig().getUrl().split("/")[2]);

                return null;
            }
        };

        new Thread(task, "dashboard-loader").start();
    }
}

