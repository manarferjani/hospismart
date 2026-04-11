package com.example.demo.infrastructure.repository;

import com.example.demo.domain.Reponse;
import com.example.demo.infrastructure.database.DatabaseConfig;

import java.sql.Connection;
import java.sql.DatabaseMetaData;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class ReponseRepository {
    private final DatabaseConfig databaseConfig;

    public ReponseRepository() {
        this(DatabaseConfig.getInstance());
    }

    public ReponseRepository(DatabaseConfig databaseConfig) {
        this.databaseConfig = databaseConfig;
    }

    public List<Reponse> findByReclamationId(Integer reclamationId) {
        List<Reponse> reponses = new ArrayList<>();

        try (Connection connection = databaseConfig.openConnection()) {
            ResponseColumns cols = resolveColumns(connection);
            try (PreparedStatement statement = connection.prepareStatement(buildFindQuery(cols))) {

                statement.setInt(1, reclamationId);
                ResultSet resultSet = statement.executeQuery();

                while (resultSet.next()) {
                    Reponse reponse = new Reponse();
                    reponse.setId(resultSet.getInt("id"));
                    reponse.setReclamationId(resultSet.getInt("reclamation_id"));
                    reponse.setContenuReponse(resultSet.getString("contenu_reponse"));

                    java.sql.Timestamp timestamp = resultSet.getTimestamp("date_reponse");
                    if (timestamp != null) {
                        reponse.setDateReponse(timestamp.toLocalDateTime());
                    }

                    reponse.setAuteurReponse(resultSet.getString("auteur_reponse"));
                    reponse.setStatut(resultSet.getString("statut"));
                    reponses.add(reponse);
                }
                resultSet.close();
            }
        } catch (SQLException e) {
            System.err.println("Erreur lors de la lecture des réponses: " + e.getMessage());
            e.printStackTrace();
            throw new RuntimeException(buildErrorMessage("Impossible de charger les réponses pour la réclamation " + reclamationId, e), e);
        } catch (Exception e) {
            System.err.println("Erreur lors de la lecture des réponses: " + e.getMessage());
            e.printStackTrace();
            throw new RuntimeException(buildErrorMessage("Impossible de charger les réponses pour la réclamation " + reclamationId, e), e);
        }

        return reponses;
    }

    public boolean create(Reponse reponse) {
        try (Connection connection = databaseConfig.openConnection()) {
            ResponseColumns cols = resolveColumns(connection);
            try (PreparedStatement statement = connection.prepareStatement(buildCreateQuery(cols))) {

                int idx = 1;
                statement.setInt(idx++, reponse.getReclamationId());
                statement.setString(idx++, reponse.getContenuReponse());
                statement.setString(idx++, reponse.getAuteurReponse() != null ? reponse.getAuteurReponse() : "Admin");

                if (cols.statut != null) {
                    statement.setString(idx++, reponse.getStatut());
                }

                if (cols.adminEmail != null) {
                    statement.setString(idx++, "admin@hospismart.com");
                }

                int rowsAffected = statement.executeUpdate();
                return rowsAffected > 0;
            }
        } catch (SQLException e) {
            System.err.println("Erreur lors de la création de la réponse: " + e.getMessage());
            throw new RuntimeException(buildErrorMessage("Impossible de créer la réponse", e), e);
        } catch (Exception e) {
            System.err.println("Erreur lors de la création de la réponse: " + e.getMessage());
            e.printStackTrace();
            throw new RuntimeException(buildErrorMessage("Impossible de créer la réponse", e), e);
        }
    }

    public boolean update(Reponse reponse) {
        try (Connection connection = databaseConfig.openConnection()) {
            ResponseColumns cols = resolveColumns(connection);
            try (PreparedStatement statement = connection.prepareStatement(buildUpdateQuery(cols))) {

                int idx = 1;
                statement.setString(idx++, reponse.getContenuReponse());
                statement.setString(idx++, reponse.getAuteurReponse() != null ? reponse.getAuteurReponse() : "Admin");

                if (cols.statut != null) {
                    statement.setString(idx++, reponse.getStatut());
                }

                statement.setInt(idx++, reponse.getId());

                int rowsAffected = statement.executeUpdate();
                return rowsAffected > 0;
            }
        } catch (SQLException e) {
            System.err.println("Erreur lors de la mise à jour de la réponse: " + e.getMessage());
            e.printStackTrace();
            throw new RuntimeException(buildErrorMessage("Impossible de modifier la réponse", e), e);
        } catch (Exception e) {
            System.err.println("Erreur lors de la mise à jour de la réponse: " + e.getMessage());
            e.printStackTrace();
            throw new RuntimeException(buildErrorMessage("Impossible de modifier la réponse", e), e);
        }
    }

    public boolean delete(Integer id) {
        try (Connection connection = databaseConfig.openConnection()) {
            ResponseColumns cols = resolveColumns(connection);
            try (PreparedStatement statement = connection.prepareStatement(buildDeleteQuery(cols))) {

                statement.setInt(1, id);
                int rowsAffected = statement.executeUpdate();
                return rowsAffected > 0;
            }
        } catch (Exception e) {
            System.err.println("Erreur lors de la suppression de la réponse: " + e.getMessage());
            e.printStackTrace();
            throw new RuntimeException(buildErrorMessage("Impossible de supprimer la réponse", e), e);
        }
    }

    private String buildDeleteQuery(ResponseColumns columns) throws SQLException {
        return "DELETE FROM reponse WHERE " + columns.id + " = ?";
    }

    public int countByReclamationId(Integer reclamationId) {
        try (Connection connection = databaseConfig.openConnection()) {
            ResponseColumns cols = resolveColumns(connection);
            try (PreparedStatement statement = connection.prepareStatement(buildCountQuery(cols))) {

                statement.setInt(1, reclamationId);
                ResultSet resultSet = statement.executeQuery();

                if (resultSet.next()) {
                    int count = resultSet.getInt("count");
                    resultSet.close();
                    return count;
                }
                resultSet.close();
            }
        } catch (SQLException e) {
            System.err.println("Erreur lors du comptage des réponses: " + e.getMessage());
            throw new RuntimeException(buildErrorMessage("Impossible de compter les réponses pour la réclamation " + reclamationId, e), e);
        } catch (Exception e) {
            System.err.println("Erreur lors du comptage des réponses: " + e.getMessage());
            throw new RuntimeException(buildErrorMessage("Impossible de compter les réponses pour la réclamation " + reclamationId, e), e);
        }

        return 0;
    }

    private String buildFindQuery(ResponseColumns columns) throws SQLException {
        return "SELECT "
                + columns.id + " AS id, "
                + columns.reclamationId + " AS reclamation_id, "
                + columns.contenu + " AS contenu_reponse, "
                + (columns.dateReponse != null ? columns.dateReponse + " AS date_reponse, " : "NULL AS date_reponse, ")
                + columns.auteur + " AS auteur_reponse, "
                + (columns.statut != null ? columns.statut + " AS statut " : "NULL AS statut ")
                + "FROM reponse WHERE " + columns.reclamationId + " = ? ORDER BY "
                + (columns.dateReponse != null ? columns.dateReponse : columns.id) + " DESC";
    }

    private String buildCreateQuery(ResponseColumns columns) throws SQLException {
        List<String> columnNames = new ArrayList<>();
        List<String> placeholders = new ArrayList<>();

        columnNames.add(columns.reclamationId);
        placeholders.add("?");

        columnNames.add(columns.contenu);
        placeholders.add("?");

        if (columns.dateReponse != null) {
            columnNames.add(columns.dateReponse);
            placeholders.add("NOW()");
        }

        columnNames.add(columns.auteur);
        placeholders.add("?");

        if (columns.statut != null) {
            columnNames.add(columns.statut);
            placeholders.add("?");
        }

        if (columns.adminEmail != null) {
            columnNames.add(columns.adminEmail);
            placeholders.add("?");
        }

        return "INSERT INTO reponse (" + String.join(", ", columnNames) + ") VALUES (" + String.join(", ", placeholders) + ")";
    }

    private String buildUpdateQuery(ResponseColumns columns) throws SQLException {
        List<String> assignments = new ArrayList<>();

        assignments.add(columns.contenu + " = ?");
        assignments.add(columns.auteur + " = ?");

        if (columns.statut != null) {
            assignments.add(columns.statut + " = ?");
        }

        return "UPDATE reponse SET " + String.join(", ", assignments) + " WHERE " + columns.id + " = ?";
    }

    private String buildCountQuery(ResponseColumns columns) throws SQLException {
        return "SELECT COUNT(*) as count FROM reponse WHERE " + columns.reclamationId + " = ?";
    }

    private ResponseColumns resolveColumns(Connection connection) throws SQLException {
        DatabaseMetaData metaData = connection.getMetaData();
        Map<String, String> columns = loadColumns(metaData, "reponse");

        String id = resolveColumn(columns, "id", "id_reponse", "reponse_id");
        String reclamationId = resolveColumn(columns, "reclamation_id", "reclamationid", "id_reclamation", "reclamationId", "reclamation");
        String contenu = resolveColumn(columns, "contenu_reponse", "contenu_response", "contenu", "message_reponse", "message", "texte_reponse", "texte", "response_content", "description");
        String dateReponse = resolveColumn(columns, "date_reponse", "created_at", "createdAt", "date_creation", "date", "timestamp");
        String auteur = resolveColumn(columns, "admin_nom", "auteur_reponse", "auteur_response", "auteur", "author", "nom_auteur", "nom_responsable");
        String statut = resolveColumn(columns, "statut", "status", "etat", "etat_reponse", "state");
        String adminEmail = resolveColumn(columns, "admin_email", "email_admin", "email", "auteur_email");

        if (id == null || reclamationId == null || contenu == null || auteur == null) {
            throw new SQLException("Schéma de la table 'reponse' incompatible. Colonnes trouvées: " + columns.values());
        }

        return new ResponseColumns(id, reclamationId, contenu, dateReponse, auteur, statut, adminEmail);
    }

    private Map<String, String> loadColumns(DatabaseMetaData metaData, String tableName) throws SQLException {
        Map<String, String> columns = new LinkedHashMap<>();

        try (ResultSet resultSet = metaData.getColumns(null, null, tableName, null)) {
            while (resultSet.next()) {
                String columnName = resultSet.getString("COLUMN_NAME");
                if (columnName != null && !columnName.isBlank()) {
                    columns.put(columnName.toLowerCase(), columnName);
                }
            }
        }

        return columns;
    }

    private String resolveColumn(Map<String, String> columns, String... candidates) {
        for (String candidate : candidates) {
            String actual = columns.get(candidate.toLowerCase());
            if (actual != null) {
                return actual;
            }
        }
        return null;
    }

    private static final class ResponseColumns {
        private final String id;
        private final String reclamationId;
        private final String contenu;
        private final String dateReponse;
        private final String auteur;
        private final String statut;
        private final String adminEmail;

        private ResponseColumns(String id, String reclamationId, String contenu, String dateReponse, String auteur, String statut, String adminEmail) {
            this.id = id;
            this.reclamationId = reclamationId;
            this.contenu = contenu;
            this.dateReponse = dateReponse;
            this.auteur = auteur;
            this.statut = statut;
            this.adminEmail = adminEmail;
        }
    }

    private String buildErrorMessage(String prefix, Exception e) {
        if (e instanceof SQLException sqlException) {
            StringBuilder builder = new StringBuilder(prefix)
                .append(" (SQLState=").append(sqlException.getSQLState())
                .append(", errorCode=").append(sqlException.getErrorCode())
                .append(") ");

            if (sqlException.getMessage() != null && !sqlException.getMessage().isBlank()) {
                builder.append(sqlException.getMessage());
            } else {
                builder.append(sqlException.getClass().getSimpleName());
            }
            return builder.toString();
        }

        return prefix + ": " + (e.getMessage() != null ? e.getMessage() : e.getClass().getSimpleName());
    }
}

