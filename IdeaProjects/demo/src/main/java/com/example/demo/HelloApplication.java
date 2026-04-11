package com.example.demo;

import javafx.application.Application;
import javafx.fxml.FXMLLoader;
import javafx.scene.Scene;
import javafx.stage.Stage;

import java.io.IOException;
import java.util.Objects;

public class HelloApplication extends Application {
    @Override
    public void start(Stage stage) throws IOException {
        FXMLLoader fxmlLoader = new FXMLLoader(HelloApplication.class.getResource("view/portal-view.fxml"));
        Scene scene = new Scene(fxmlLoader.load(), 1500, 950);
        // Force l'application du thème global même si une vue FXML omet le stylesheet.
        scene.getStylesheets().add(Objects.requireNonNull(
            HelloApplication.class.getResource("app.css"),
            "app.css introuvable"
        ).toExternalForm());
        stage.setTitle("HospiSmart - Portail");
        stage.setMinWidth(1000);
        stage.setMinHeight(750);
        stage.setScene(scene);
        stage.show();
    }
}
