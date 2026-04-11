package com.example.demo.infrastructure.repository;

import com.example.demo.domain.User;
import com.example.demo.infrastructure.database.DatabaseConfig;

import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.List;

public class UserRepository {
    private final DatabaseConfig databaseConfig;

    public UserRepository() {
        this(DatabaseConfig.getInstance());
    }

    public UserRepository(DatabaseConfig databaseConfig) {
        this.databaseConfig = databaseConfig;
    }

    public List<User> findAll() {
        List<User> users = new ArrayList<>();
        String query = "SELECT id, nom, prenom, email, telephone, role, statut FROM user LIMIT 100";

        try (Connection connection = databaseConfig.openConnection();
             Statement statement = connection.createStatement();
             ResultSet resultSet = statement.executeQuery(query)) {

            while (resultSet.next()) {
                User user = new User();
                user.setId(resultSet.getInt("id"));
                user.setNom(resultSet.getString("nom"));
                user.setPrenom(resultSet.getString("prenom"));
                user.setEmail(resultSet.getString("email"));
                user.setTelephone(resultSet.getString("telephone"));
                user.setRole(resultSet.getString("role"));
                user.setStatut(resultSet.getString("statut"));
                users.add(user);
            }
        } catch (Exception e) {
            System.err.println("Erreur lors de la lecture des utilisateurs: " + e.getMessage());
            e.printStackTrace();
        }

        return users;
    }
}

