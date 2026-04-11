package com.example.demo.infrastructure.database;

import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.assertFalse;
import static org.junit.jupiter.api.Assertions.assertNotNull;
import static org.junit.jupiter.api.Assertions.assertTrue;

class DatabaseConfigTest {

    @Test
    void loadsDefaultOrExternalConfiguration() {
        DatabaseConfig config = DatabaseConfig.getInstance();

        assertNotNull(config);
        assertNotNull(config.getUrl());
        assertFalse(config.getUrl().trim().isEmpty());
        assertNotNull(config.getUsername());
        assertNotNull(config.getDriverClassName());
        assertFalse(config.getDriverClassName().trim().isEmpty());
        assertTrue(config.getLoginTimeoutSeconds() > 0);
    }
}

