package com.example.demo.controller;

import com.example.demo.domain.Reclamation;
import com.example.demo.service.DatabaseService;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.concurrent.Task;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;

import java.time.format.DateTimeFormatter;
import java.util.List;
import java.util.Optional;

public class ReclamationControllerCRUD {
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
    private TextField titreInput;
    @FXML
    private TextField nomPatientInput;
    @FXML
    private TextField emailInput;
    @FXML
    private ComboBox<String> categorieCombo;
    @FXML
    private ComboBox<String> prioriteCombo;
    @FXML
    private ComboBox<String> statutCombo;
    @FXML
    private TextArea descriptionInput;

    @FXML
    private Label statusLabel;

    private final DatabaseService databaseService = new DatabaseService();
    private Reclamation editingReclamation = null;

    @FXML
    public void initialize() {
        setupColumns();
        setupComboBoxes();
        hideInternalIdColumn();
        reclamationTable.setColumnResizePolicy(TableView.CONSTRAINED_RESIZE_POLICY_ALL_COLUMNS);
        loadReclamations();
    }

    private void hideInternalIdColumn() {
        idColumn.setVisible(false);
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

    private void setupComboBoxes() {
        ObservableList<String> categories = FXCollections.observableArrayList(
            "Service", "Hygiène", "Facturation", "Personnel", "Médicaments"
        );
        categorieCombo.setItems(categories);

        ObservableList<String> priorites = FXCollections.observableArrayList(
            "Basse", "Normale", "Haute", "Urgente"
        );
        prioriteCombo.setItems(priorites);

        ObservableList<String> statuts = FXCollections.observableArrayList(
            "En attente", "En cours", "Résolue", "Fermée", "Rejetée"
        );
        statutCombo.setItems(statuts);
    }

    @FXML
    public void loadReclamations() {
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
            statusLabel.setText("Erreur lors du chargement");
            event.getSource().getException().printStackTrace();
        });

        Thread thread = new Thread(loadTask, "reclamation-loader");
        thread.setDaemon(true);
        thread.start();
    }

    @FXML
    public void onAddReclamation() {
        if (!validateForm()) {
            showError("Validation", "Tous les champs doivent être remplis");
            return;
        }

        Reclamation reclamation = new Reclamation();
        reclamation.setTitre(titreInput.getText());
        reclamation.setNomPatient(nomPatientInput.getText());
        reclamation.setEmail(emailInput.getText());
        reclamation.setCategorie(categorieCombo.getValue());
        reclamation.setPriorite(prioriteCombo.getValue());
        reclamation.setStatut(statutCombo.getValue());
        reclamation.setDescription(descriptionInput.getText());

        Task<Boolean> saveTask = new Task<>() {
            @Override
            protected Boolean call() {
                if (editingReclamation != null) {
                    // Mode modification
                    reclamation.setId(editingReclamation.getId());
                    return databaseService.updateReclamation(reclamation);
                } else {
                    // Mode création
                    return databaseService.createReclamation(reclamation);
                }
            }
        };

        saveTask.setOnSucceeded(event -> {
            boolean success = saveTask.getValue();
            if (success) {
                showInfo("Succès", editingReclamation != null ?
                    "Réclamation modifiée avec succès" : "Réclamation créée avec succès");
                onClearForm();
                loadReclamations();
            } else {
                showError("Erreur", "Impossible de sauvegarder la réclamation");
            }
        });

        saveTask.setOnFailed(event -> {
            showError("Erreur", "Une erreur s'est produite lors de la sauvegarde");
        });

        Thread thread = new Thread(saveTask, "reclamation-save");
        thread.setDaemon(true);
        thread.start();
    }

    @FXML
    public void onClearForm() {
        titreInput.clear();
        nomPatientInput.clear();
        emailInput.clear();
        descriptionInput.clear();
        categorieCombo.setValue(null);
        prioriteCombo.setValue(null);
        statutCombo.setValue(null);
        editingReclamation = null;
    }

    @FXML
    public void onEditReclamation() {
        Reclamation selected = reclamationTable.getSelectionModel().getSelectedItem();
        if (selected == null) {
            showError("Sélection", "Veuillez sélectionner une réclamation à modifier");
            return;
        }

        editingReclamation = selected;
        titreInput.setText(selected.getTitre());
        nomPatientInput.setText(selected.getNomPatient());
        emailInput.setText(selected.getEmail());
        descriptionInput.setText(selected.getDescription());
        categorieCombo.setValue(selected.getCategorie());
        prioriteCombo.setValue(selected.getPriorite());
        statutCombo.setValue(selected.getStatut());
    }

    @FXML
    public void onDeleteReclamation() {
        Reclamation selected = reclamationTable.getSelectionModel().getSelectedItem();
        if (selected == null) {
            showError("Sélection", "Veuillez sélectionner une réclamation à supprimer");
            return;
        }

        Optional<ButtonType> result = showConfirm("Confirmation",
            "Êtes-vous sûr de vouloir supprimer cette réclamation ?");

        if (result.isPresent() && result.get() == ButtonType.OK) {
            Task<Boolean> deleteTask = new Task<>() {
                @Override
                protected Boolean call() {
                    return databaseService.deleteReclamation(selected.getId());
                }
            };

            deleteTask.setOnSucceeded(event -> {
                boolean success = deleteTask.getValue();
                if (success) {
                    showInfo("Succès", "Réclamation supprimée avec succès");
                    loadReclamations();
                } else {
                    showError("Erreur", "Impossible de supprimer la réclamation");
                }
            });

            new Thread(deleteTask, "reclamation-delete").start();
        }
    }

    private boolean validateForm() {
        return !titreInput.getText().trim().isEmpty() &&
               !nomPatientInput.getText().trim().isEmpty() &&
               !emailInput.getText().trim().isEmpty() &&
               categorieCombo.getValue() != null &&
               prioriteCombo.getValue() != null &&
               statutCombo.getValue() != null &&
               !descriptionInput.getText().trim().isEmpty();
    }

    private void showError(String title, String message) {
        Alert alert = new Alert(Alert.AlertType.ERROR);
        alert.setTitle(title);
        alert.setContentText(message);
        alert.showAndWait();
    }

    private void showInfo(String title, String message) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle(title);
        alert.setContentText(message);
        alert.showAndWait();
    }

    private Optional<ButtonType> showConfirm(String title, String message) {
        Alert alert = new Alert(Alert.AlertType.CONFIRMATION);
        alert.setTitle(title);
        alert.setContentText(message);
        return alert.showAndWait();
    }
}

