package com.example.demo.service;

import com.example.demo.infrastructure.database.DatabaseConfig;
import com.example.demo.domain.Reclamation;
import com.example.demo.infrastructure.repository.ReclamationRepository;
import java.util.List;
import com.example.demo.domain.User;
import com.example.demo.domain.Service;
import com.example.demo.domain.Reponse;
import com.example.demo.infrastructure.repository.UserRepository;
import com.example.demo.infrastructure.repository.ServiceRepository;
import com.example.demo.infrastructure.repository.ReponseRepository;

public final class DatabaseService {
    private final DatabaseConfig databaseConfig;
    private final ReclamationRepository reclamationRepository;
    private final UserRepository userRepository;
    private final ServiceRepository serviceRepository;
    private final ReponseRepository reponseRepository;

    public DatabaseService() {
        this(DatabaseConfig.getInstance());
    }

    public DatabaseService(DatabaseConfig databaseConfig) {
        this.databaseConfig = databaseConfig;
        this.reclamationRepository = new ReclamationRepository(databaseConfig);
        this.userRepository = new UserRepository(databaseConfig);
        this.serviceRepository = new ServiceRepository(databaseConfig);
        this.reponseRepository = new ReponseRepository(databaseConfig);
    }

    public boolean isDatabaseReachable() {
        return databaseConfig.canConnect();
    }

    public List<Reclamation> loadAllReclamations() {
        return reclamationRepository.findAll();
    }

    public Reclamation loadReclamationById(Integer id) {
        return reclamationRepository.findById(id);
    }

    public int countReclamationsByStatut(String statut) {
        return reclamationRepository.countByStatut(statut);
    }

    public boolean createReclamation(Reclamation reclamation) {
        return reclamationRepository.create(reclamation);
    }

    public boolean updateReclamation(Reclamation reclamation) {
        return reclamationRepository.update(reclamation);
    }

    public boolean deleteReclamation(Integer id) {
        return reclamationRepository.delete(id);
    }

    public List<User> loadAllUsers() {
        return userRepository.findAll();
    }

    public List<Service> loadAllServices() {
        return serviceRepository.findAll();
    }

    public int countServices() {
        return serviceRepository.countAll();
    }

    public DatabaseConfig getDatabaseConfig() {
        return databaseConfig;
    }

    public List<Reponse> loadReponsesByReclamation(Integer reclamationId) {
        return reponseRepository.findByReclamationId(reclamationId);
    }

    public boolean createReponse(Reponse reponse) {
        return reponseRepository.create(reponse);
    }

    public boolean updateReponse(Reponse reponse) {
        return reponseRepository.update(reponse);
    }

    public boolean deleteReponse(Integer id) {
        return reponseRepository.delete(id);
    }

    public int countReponses(Integer reclamationId) {
        return reponseRepository.countByReclamationId(reclamationId);
    }
}
