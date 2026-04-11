package com.example.demo.controller;

import com.example.demo.HelloApplication;


import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Node;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.Alert;
import javafx.stage.Stage;

import java.io.IOException;
import java.util.Objects;

public class PortalController {

    @FXML
    public void openFrontOffice(ActionEvent event) {
        try {
            switchScene(event, "frontoffice-view.fxml", "HospiSmart - Front Office");
        } catch (IOException e) {
            showNavigationError("Front Office", e);
        }
    }

    @FXML
    public void openBackOffice(ActionEvent event) {
        try {
            switchScene(event, "main-view.fxml", "HospiSmart - Back Office");
        } catch (IOException e) {
            showNavigationError("Back Office", e);
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
}


