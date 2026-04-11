package com.example.demo.controller;

import com.example.demo.domain.Reclamation;
import com.example.demo.domain.Reponse;
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

public class BackOfficeController {
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
    private TableColumn<Reclamation, Integer> reponseCountColumn;

    @FXML
    private Label detailTitre;
    @FXML
    private Label detailPatient;
    @FXML
    private Label detailStatut;
    @FXML
    private Label detailCategorie;
    @FXML
    private Label detailPriorite;
    @FXML
    private TextArea detailDescription;

    @FXML
    private TableView<Reponse> responseTable;
    @FXML
    private TableColumn<Reponse, String> responseDateColumn;
    @FXML
    private TableColumn<Reponse, String> responseAuteurColumn;
    @FXML
    private TableColumn<Reponse, String> responseStatutColumn;
    @FXML
    private TableColumn<Reponse, String> responseContenuColumn;

    @FXML
    private TextField auteurInput;
    @FXML
    private TextArea reponseInput;
    @FXML
    private ComboBox<String> statutReponseCombo;

    @FXML
    private Label statusLabel;

    private final DatabaseService databaseService = new DatabaseService();
    private Reclamation selectedReclamation = null;
    private Reponse editingReponse = null;

    @FXML
    public void initialize() {
        setupColumns();
        setupComboBoxes();
        hideInternalIds();
        reclamationTable.setColumnResizePolicy(TableView.CONSTRAINED_RESIZE_POLICY_ALL_COLUMNS);
        responseTable.setColumnResizePolicy(TableView.CONSTRAINED_RESIZE_POLICY_ALL_COLUMNS);
        loadReclamations();

        // Écouter la sélection de réclamations
        reclamationTable.getSelectionModel().selectedItemProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal != null) {
                selectedReclamation = newVal;
                editingReponse = null;
                onClearReponseForm();
                displayReclamationDetails(newVal);
            }
        });

        responseTable.getSelectionModel().selectedItemProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal != null) {
                loadReponseIntoForm(newVal);
            }
        });
    }

    private void setupColumns() {
        idColumn.setCellValueFactory(new PropertyValueFactory<>("id"));
        titreColumn.setCellValueFactory(new PropertyValueFactory<>("titre"));
        patientColumn.setCellValueFactory(new PropertyValueFactory<>("nomPatient"));
        statutColumn.setCellValueFactory(new PropertyValueFactory<>("statut"));

        // Colonne personnalisée pour le compte de réponses
        reponseCountColumn.setCellValueFactory(cellData -> {
            Reclamation rec = cellData.getValue();
            try {
                int count = databaseService.countReponses(rec.getId());
                return new javafx.beans.property.SimpleObjectProperty<>(count);
            } catch (Exception e) {
                System.err.println("Erreur lors du comptage des réponses pour ID " + rec.getId() + ": " + e.getMessage());
                return new javafx.beans.property.SimpleObjectProperty<>(0);
            }
        });

        responseDateColumn.setCellValueFactory(cellData -> {
            Reponse response = cellData.getValue();
            String formatted = response.getDateReponse() == null ? "" :
                response.getDateReponse().format(DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm"));
            return new javafx.beans.property.SimpleStringProperty(formatted);
        });
        responseAuteurColumn.setCellValueFactory(new PropertyValueFactory<>("auteurReponse"));
        responseStatutColumn.setCellValueFactory(new PropertyValueFactory<>("statut"));
        responseContenuColumn.setCellValueFactory(cellData -> {
            String value = cellData.getValue().getContenuReponse();
            return new javafx.beans.property.SimpleStringProperty(value == null ? "" : value);
        });
    }

    private void hideInternalIds() {
        idColumn.setVisible(false);
    }

    private void setupComboBoxes() {
        ObservableList<String> statuts = FXCollections.observableArrayList(
            "En attente", "En cours", "Résolue", "Fermée", "Escaladée"
        );
        statutReponseCombo.setItems(statuts);
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
            statusLabel.setText("Réclamations chargées: " + reclamations.size());
        });

        loadTask.setOnFailed(event -> {
            Throwable exception = event.getSource().getException();
            statusLabel.setText("Erreur lors du chargement");
            showError("Erreur", formatExceptionMessage("Impossible de charger les réclamations", exception));
        });

        new Thread(loadTask, "backoffice-loader").start();
    }

    private void displayReclamationDetails(Reclamation reclamation) {
        detailTitre.setText(reclamation.getTitre());
        detailPatient.setText(reclamation.getNomPatient());
        detailStatut.setText(reclamation.getStatut());
        detailCategorie.setText(reclamation.getCategorie());
        detailPriorite.setText(reclamation.getPriorite());
        detailDescription.setText(reclamation.getDescription());

        // Charger les réponses pour cette réclamation
        loadReponses(reclamation.getId());
    }

    private void loadReponses(Integer reclamationId) {
        Task<List<Reponse>> loadTask = new Task<>() {
            @Override
            protected List<Reponse> call() {
                return databaseService.loadReponsesByReclamation(reclamationId);
            }
        };

        loadTask.setOnSucceeded(event -> {
            List<Reponse> reponses = loadTask.getValue();
            responseTable.setItems(FXCollections.observableArrayList(reponses));
        });

        loadTask.setOnFailed(event -> {
            Throwable exception = event.getSource().getException();
            showError("Erreur", formatExceptionMessage("Impossible de charger les réponses", exception));
        });

        new Thread(loadTask, "reponse-loader").start();
    }

    @FXML
    public void refreshSelectedReponses() {
        if (selectedReclamation != null) {
            loadReponses(selectedReclamation.getId());
        }
    }

    @FXML
    public void onSendReponse() {
        if (selectedReclamation == null) {
            showError("Erreur", "Veuillez sélectionner une réclamation d'abord");
            return;
        }

        if (!validateReponseForm()) {
            showError("Validation", "Tous les champs doivent être remplis");
            return;
        }

        Reponse reponse = new Reponse();
        reponse.setContenuReponse(reponseInput.getText());
        reponse.setAuteurReponse(auteurInput.getText());
        reponse.setStatut(statutReponseCombo.getValue());
        reponse.setReclamationId(selectedReclamation.getId());

        Task<Boolean> saveTask = new Task<>() {
            @Override
            protected Boolean call() {
                if (editingReponse != null) {
                    reponse.setId(editingReponse.getId());
                    return databaseService.updateReponse(reponse);
                }
                return databaseService.createReponse(reponse);
            }
        };

        saveTask.setOnSucceeded(event -> {
            boolean success = saveTask.getValue();
            if (success) {
                showInfo("Succès", editingReponse != null ? "Réponse modifiée avec succès" : "Réponse envoyée avec succès");
                onClearReponseForm();
                loadReponses(selectedReclamation.getId());
            } else {
                showError("Erreur", "Impossible d'envoyer la réponse: la base de données a refusé l'opération");
            }
        });

        saveTask.setOnFailed(event -> {
            Throwable exception = event.getSource().getException();
            showError("Erreur", formatExceptionMessage("Impossible d'envoyer la réponse", exception));
            if (exception != null) {
                exception.printStackTrace();
            }
        });

        new Thread(saveTask, "reponse-save").start();
    }

    @FXML
    public void onClearReponseForm() {
        auteurInput.clear();
        reponseInput.clear();
        statutReponseCombo.setValue(null);
        editingReponse = null;
        responseTable.getSelectionModel().clearSelection();
    }

    @FXML
    public void onEditReponse() {
        Reponse selected = responseTable.getSelectionModel().getSelectedItem();
        if (selected == null) {
            showError("Sélection", "Veuillez sélectionner une réponse à modifier");
            return;
        }
        loadReponseIntoForm(selected);
    }

    @FXML
    public void onDeleteReponse() {
        Reponse selected = responseTable.getSelectionModel().getSelectedItem();
        if (selected == null) {
            showError("Sélection", "Veuillez sélectionner une réponse à supprimer");
            return;
        }

        Optional<ButtonType> result = showConfirm("Confirmation", "Êtes-vous sûr de vouloir supprimer cette réponse ?");
        if (result.isPresent() && result.get() == ButtonType.OK) {
            Task<Boolean> deleteTask = new Task<>() {
                @Override
                protected Boolean call() {
                    return databaseService.deleteReponse(selected.getId());
                }
            };

            deleteTask.setOnSucceeded(event -> {
                if (Boolean.TRUE.equals(deleteTask.getValue())) {
                    showInfo("Succès", "Réponse supprimée avec succès");
                    onClearReponseForm();
                    loadReponses(selectedReclamation.getId());
                } else {
                    showError("Erreur", "Impossible de supprimer la réponse");
                }
            });

            new Thread(deleteTask, "reponse-delete").start();
        }
    }

    private void loadReponseIntoForm(Reponse selected) {
        editingReponse = selected;
        auteurInput.setText(selected.getAuteurReponse());
        reponseInput.setText(selected.getContenuReponse());
        statutReponseCombo.setValue(selected.getStatut());
    }

    private boolean validateReponseForm() {
        return !auteurInput.getText().trim().isEmpty() &&
               !reponseInput.getText().trim().isEmpty() &&
               statutReponseCombo.getValue() != null;
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

    private String formatExceptionMessage(String prefix, Throwable exception) {
        if (exception == null) {
            return prefix + ": une erreur inconnue s'est produite";
        }

        Throwable root = exception;
        while (root.getCause() != null && root.getCause() != root) {
            root = root.getCause();
        }

        StringBuilder builder = new StringBuilder(prefix).append(": ");
        if (root.getMessage() != null && !root.getMessage().isBlank()) {
            builder.append(root.getMessage());
        } else {
            builder.append(root.getClass().getSimpleName());
        }

        if (root != exception && exception.getMessage() != null && !exception.getMessage().isBlank()) {
            builder.append(" (cause: ").append(exception.getMessage()).append(")");
        }

        return builder.toString();
    }

    private Optional<ButtonType> showConfirm(String title, String message) {
        Alert alert = new Alert(Alert.AlertType.CONFIRMATION);
        alert.setTitle(title);
        alert.setContentText(message);
        return alert.showAndWait();
    }
}










