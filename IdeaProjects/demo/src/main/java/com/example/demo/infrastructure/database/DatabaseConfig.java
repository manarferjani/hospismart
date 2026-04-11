package com.example.demo.infrastructure.database;

import java.io.IOException;
import java.io.InputStream;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.util.Objects;
import java.util.Properties;

public final class DatabaseConfig {
    private static final String CONFIG_RESOURCE = "application.properties";
    private static final String ENV_URL = "DB_URL";
    private static final String ENV_USERNAME = "DB_USERNAME";
    private static final String ENV_PASSWORD = "DB_PASSWORD";
    private static final String ENV_DRIVER = "DB_DRIVER";
    private static final String ENV_LOGIN_TIMEOUT = "DB_LOGIN_TIMEOUT_SECONDS";

    private static final String DEFAULT_URL = "jdbc:mysql://localhost:3306/your_database?useSSL=false&serverTimezone=UTC&allowPublicKeyRetrieval=true";
    private static final String DEFAULT_USERNAME = "root";
    private static final String DEFAULT_PASSWORD = "";
    private static final String DEFAULT_DRIVER = "com.mysql.cj.jdbc.Driver";
    private static final int DEFAULT_LOGIN_TIMEOUT_SECONDS = 5;

    private static final DatabaseConfig INSTANCE = load();

    private final String url;
    private final String username;
    private final String password;
    private final String driverClassName;
    private final int loginTimeoutSeconds;

    private DatabaseConfig(String url, String username, String password, String driverClassName, int loginTimeoutSeconds) {
        this.url = Objects.requireNonNull(url, "url must not be null");
        this.username = Objects.requireNonNull(username, "username must not be null");
        this.password = Objects.requireNonNull(password, "password must not be null");
        this.driverClassName = Objects.requireNonNull(driverClassName, "driverClassName must not be null");
        this.loginTimeoutSeconds = Math.max(1, loginTimeoutSeconds);
    }

    public static DatabaseConfig getInstance() {
        return INSTANCE;
    }

    public Connection openConnection() throws SQLException {
        loadDriver();
        DriverManager.setLoginTimeout(loginTimeoutSeconds);
        return DriverManager.getConnection(url, username, password);
    }

    public String getUrl() {
        return url;
    }

    public String getUsername() {
        return username;
    }

    public String getDriverClassName() {
        return driverClassName;
    }

    public int getLoginTimeoutSeconds() {
        return loginTimeoutSeconds;
    }

    public boolean canConnect() {
        try (Connection connection = openConnection()) {
            return connection.isValid(loginTimeoutSeconds);
        } catch (SQLException | IllegalStateException e) {
            return false;
        }
    }

    private void loadDriver() {
        try {
            Class.forName(driverClassName);
        } catch (ClassNotFoundException e) {
            throw new IllegalStateException("Impossible de charger le driver JDBC MySQL: " + driverClassName, e);
        }
    }

    private static DatabaseConfig load() {
        Properties properties = new Properties();
        try (InputStream inputStream = DatabaseConfig.class.getClassLoader().getResourceAsStream(CONFIG_RESOURCE)) {
            if (inputStream != null) {
                properties.load(inputStream);
            }
        } catch (IOException e) {
            throw new IllegalStateException("Impossible de lire le fichier de configuration MySQL: " + CONFIG_RESOURCE, e);
        }

        String url = resolve(properties, "db.url", ENV_URL, DEFAULT_URL);
        String username = resolve(properties, "db.username", ENV_USERNAME, DEFAULT_USERNAME);
        String password = resolve(properties, "db.password", ENV_PASSWORD, DEFAULT_PASSWORD);
        String driverClassName = resolve(properties, "db.driver", ENV_DRIVER, DEFAULT_DRIVER);
        int loginTimeoutSeconds = parseInteger(resolve(properties, "db.loginTimeoutSeconds", ENV_LOGIN_TIMEOUT, String.valueOf(DEFAULT_LOGIN_TIMEOUT_SECONDS)), DEFAULT_LOGIN_TIMEOUT_SECONDS);

        return new DatabaseConfig(url, username, password, driverClassName, loginTimeoutSeconds);
    }

    private static String resolve(Properties properties, String propertyName, String envName, String defaultValue) {
        String envValue = System.getenv(envName);
        if (envValue != null && !envValue.trim().isEmpty()) {
            return envValue.trim();
        }

        String propertyValue = properties.getProperty(propertyName);
        if (propertyValue != null && !propertyValue.trim().isEmpty()) {
            return propertyValue.trim();
        }

        return defaultValue;
    }

    private static int parseInteger(String value, int defaultValue) {
        try {
            return Integer.parseInt(value.trim());
        } catch (NumberFormatException e) {
            return defaultValue;
        }
    }
}

