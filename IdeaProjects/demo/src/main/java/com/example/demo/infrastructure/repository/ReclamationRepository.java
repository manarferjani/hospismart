package com.example.demo.infrastructure.repository;

import com.example.demo.domain.Reclamation;
import com.example.demo.infrastructure.database.DatabaseConfig;

import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.Statement;
import java.sql.PreparedStatement;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;

public class ReclamationRepository {
    private final DatabaseConfig databaseConfig;

    public ReclamationRepository() {
        this(DatabaseConfig.getInstance());
    }

    public ReclamationRepository(DatabaseConfig databaseConfig) {
        this.databaseConfig = databaseConfig;
    }

    public List<Reclamation> findAll() {
        List<Reclamation> reclamations = new ArrayList<>();
        String query = "SELECT id, titre, description, date_creation, email, nom_patient, statut, categorie, priorite, etat_mental FROM reclamation ORDER BY date_creation DESC";

        try (Connection connection = databaseConfig.openConnection();
             PreparedStatement statement = connection.prepareStatement(query);
             ResultSet resultSet = statement.executeQuery()) {

            while (resultSet.next()) {
                Reclamation reclamation = new Reclamation();
                reclamation.setId(resultSet.getInt("id"));
                reclamation.setTitre(resultSet.getString("titre"));
                reclamation.setDescription(resultSet.getString("description"));

                java.sql.Timestamp timestamp = resultSet.getTimestamp("date_creation");
                if (timestamp != null) {
                    reclamation.setDateCreation(timestamp.toLocalDateTime());
                }

                reclamation.setEmail(resultSet.getString("email"));
                reclamation.setNomPatient(resultSet.getString("nom_patient"));
                reclamation.setStatut(resultSet.getString("statut"));
                reclamation.setCategorie(resultSet.getString("categorie"));
                reclamation.setPriorite(resultSet.getString("priorite"));
                reclamation.setEtatMental(resultSet.getString("etat_mental"));

                reclamations.add(reclamation);
            }
        } catch (Exception e) {
            System.err.println("Erreur lors de la lecture des réclamations: " + e.getMessage());
            e.printStackTrace();
        }

        return reclamations;
    }

    public Reclamation findById(Integer id) {
        String query = "SELECT id, titre, description, date_creation, email, nom_patient, statut, categorie, priorite, etat_mental FROM reclamation WHERE id = ?";

        try (Connection connection = databaseConfig.openConnection();
             PreparedStatement statement = connection.prepareStatement(query)) {

            statement.setInt(1, id);
            try (ResultSet resultSet = statement.executeQuery()) {
                if (resultSet.next()) {
                    Reclamation reclamation = new Reclamation();
                    reclamation.setId(resultSet.getInt("id"));
                    reclamation.setTitre(resultSet.getString("titre"));
                    reclamation.setDescription(resultSet.getString("description"));

                    java.sql.Timestamp timestamp = resultSet.getTimestamp("date_creation");
                    if (timestamp != null) {
                        reclamation.setDateCreation(timestamp.toLocalDateTime());
                    }

                    reclamation.setEmail(resultSet.getString("email"));
                    reclamation.setNomPatient(resultSet.getString("nom_patient"));
                    reclamation.setStatut(resultSet.getString("statut"));
                    reclamation.setCategorie(resultSet.getString("categorie"));
                    reclamation.setPriorite(resultSet.getString("priorite"));
                    reclamation.setEtatMental(resultSet.getString("etat_mental"));

                    return reclamation;
                }
            }
        } catch (Exception e) {
            System.err.println("Erreur lors de la lecture de la réclamation: " + e.getMessage());
            e.printStackTrace();
        }

        return null;
    }

    public int countByStatut(String statut) {
        String query = "SELECT COUNT(*) as count FROM reclamation WHERE statut = ?";

        try (Connection connection = databaseConfig.openConnection();
             PreparedStatement statement = connection.prepareStatement(query)) {

            statement.setString(1, statut);
            try (ResultSet resultSet = statement.executeQuery()) {
                if (resultSet.next()) {
                    return resultSet.getInt("count");
                }
            }
        } catch (Exception e) {
            System.err.println("Erreur lors du comptage des réclamations: " + e.getMessage());
        }

        return 0;
    }

    public boolean create(Reclamation reclamation) {
        String query = "INSERT INTO reclamation (titre, description, date_creation, email, nom_patient, statut, categorie, priorite, etat_mental) " +
                "VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?)";

        try (Connection connection = databaseConfig.openConnection();
             PreparedStatement statement = connection.prepareStatement(query)) {

            statement.setString(1, reclamation.getTitre());
            statement.setString(2, reclamation.getDescription());
            statement.setString(3, reclamation.getEmail());
            statement.setString(4, reclamation.getNomPatient());
            statement.setString(5, reclamation.getStatut());
            statement.setString(6, reclamation.getCategorie());
            statement.setString(7, reclamation.getPriorite());
            statement.setString(8, reclamation.getEtatMental());

            int rowsAffected = statement.executeUpdate();
            return rowsAffected > 0;
        } catch (Exception e) {
            System.err.println("Erreur lors de la création de la réclamation: " + e.getMessage());
            e.printStackTrace();
            return false;
        }
    }

    public boolean update(Reclamation reclamation) {
        String query = "UPDATE reclamation SET titre = ?, description = ?, email = ?, nom_patient = ?, statut = ?, categorie = ?, priorite = ?, etat_mental = ? WHERE id = ?";

        try (Connection connection = databaseConfig.openConnection();
             PreparedStatement statement = connection.prepareStatement(query)) {

            statement.setString(1, reclamation.getTitre());
            statement.setString(2, reclamation.getDescription());
            statement.setString(3, reclamation.getEmail());
            statement.setString(4, reclamation.getNomPatient());
            statement.setString(5, reclamation.getStatut());
            statement.setString(6, reclamation.getCategorie());
            statement.setString(7, reclamation.getPriorite());
            statement.setString(8, reclamation.getEtatMental());
            statement.setInt(9, reclamation.getId());

            int rowsAffected = statement.executeUpdate();
            return rowsAffected > 0;
        } catch (Exception e) {
            System.err.println("Erreur lors de la mise à jour de la réclamation: " + e.getMessage());
            e.printStackTrace();
            return false;
        }
    }

    public boolean delete(Integer id) {
        String query = "DELETE FROM reclamation WHERE id = ?";

        try (Connection connection = databaseConfig.openConnection();
             PreparedStatement statement = connection.prepareStatement(query)) {

            statement.setInt(1, id);
            int rowsAffected = statement.executeUpdate();
            return rowsAffected > 0;
        } catch (Exception e) {
            System.err.println("Erreur lors de la suppression de la réclamation: " + e.getMessage());
            e.printStackTrace();
            return false;
        }
    }
}

