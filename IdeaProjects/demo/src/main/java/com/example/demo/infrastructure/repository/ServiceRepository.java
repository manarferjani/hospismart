package com.example.demo.infrastructure.repository;

import com.example.demo.domain.Service;
import com.example.demo.infrastructure.database.DatabaseConfig;

import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.List;

public class ServiceRepository {
    private final DatabaseConfig databaseConfig;

    public ServiceRepository() {
        this(DatabaseConfig.getInstance());
    }

    public ServiceRepository(DatabaseConfig databaseConfig) {
        this.databaseConfig = databaseConfig;
    }

    public List<Service> findAll() {
        List<Service> services = new ArrayList<>();
        String query = "SELECT id, nom, description, responsable, capacite FROM service LIMIT 100";

        try (Connection connection = databaseConfig.openConnection();
             Statement statement = connection.createStatement();
             ResultSet resultSet = statement.executeQuery(query)) {

            while (resultSet.next()) {
                Service service = new Service();
                service.setId(resultSet.getInt("id"));
                service.setNom(resultSet.getString("nom"));
                service.setDescription(resultSet.getString("description"));
                service.setResponsable(resultSet.getString("responsable"));
                service.setCapacite(resultSet.getInt("capacite"));
                services.add(service);
            }
        } catch (Exception e) {
            System.err.println("Erreur lors de la lecture des services: " + e.getMessage());
            e.printStackTrace();
        }

        return services;
    }

    public int countAll() {
        String query = "SELECT COUNT(*) as count FROM service";

        try (Connection connection = databaseConfig.openConnection();
             Statement statement = connection.createStatement();
             ResultSet resultSet = statement.executeQuery(query)) {

            if (resultSet.next()) {
                return resultSet.getInt("count");
            }
        } catch (Exception e) {
            System.err.println("Erreur lors du comptage des services: " + e.getMessage());
        }

        return 0;
    }
}

