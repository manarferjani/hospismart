module com.example.demo {
    requires javafx.controls;
    requires javafx.fxml;
    requires java.sql;


    opens com.example.demo to javafx.fxml;
    opens com.example.demo.domain to javafx.fxml, javafx.base;
    opens com.example.demo.service to javafx.fxml;
    opens com.example.demo.infrastructure.repository to javafx.fxml;
    opens com.example.demo.controller to javafx.fxml;

    exports com.example.demo;
    exports com.example.demo.controller;
}
