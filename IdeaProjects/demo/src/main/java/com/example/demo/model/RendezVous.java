package com.example.demo.domain;

import java.time.LocalDateTime;

public class RendezVous {
    private Integer id;
    private Integer patientId;
    private Integer medecin;
    private LocalDateTime dateRendezVous;
    private String raison;
    private String statut;
    private String lieu;

    public RendezVous() {
    }

    public RendezVous(Integer id, Integer patientId, Integer medecin, LocalDateTime dateRendezVous,
                      String raison, String statut, String lieu) {
        this.id = id;
        this.patientId = patientId;
        this.medecin = medecin;
        this.dateRendezVous = dateRendezVous;
        this.raison = raison;
        this.statut = statut;
        this.lieu = lieu;
    }

    // Getters and Setters
    public Integer getId() {
        return id;
    }

    public void setId(Integer id) {
        this.id = id;
    }

    public Integer getPatientId() {
        return patientId;
    }

    public void setPatientId(Integer patientId) {
        this.patientId = patientId;
    }

    public Integer getMedecin() {
        return medecin;
    }

    public void setMedecin(Integer medecin) {
        this.medecin = medecin;
    }

    public LocalDateTime getDateRendezVous() {
        return dateRendezVous;
    }

    public void setDateRendezVous(LocalDateTime dateRendezVous) {
        this.dateRendezVous = dateRendezVous;
    }

    public String getRaison() {
        return raison;
    }

    public void setRaison(String raison) {
        this.raison = raison;
    }

    public String getStatut() {
        return statut;
    }

    public void setStatut(String statut) {
        this.statut = statut;
    }

    public String getLieu() {
        return lieu;
    }

    public void setLieu(String lieu) {
        this.lieu = lieu;
    }

    @Override
    public String toString() {
        return "RendezVous{" +
                "id=" + id +
                ", dateRendezVous=" + dateRendezVous +
                ", statut='" + statut + '\'' +
                '}';
    }
}

