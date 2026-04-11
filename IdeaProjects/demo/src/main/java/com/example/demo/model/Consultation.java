package com.example.demo.domain;

import java.time.LocalDateTime;

public class Consultation {
    private Integer id;
    private Integer patientId;
    private Integer medecin;
    private LocalDateTime dateConsultation;
    private String diagnostic;
    private String traitement;
    private String notes;

    public Consultation() {
    }

    public Consultation(Integer id, Integer patientId, Integer medecin, LocalDateTime dateConsultation,
                       String diagnostic, String traitement, String notes) {
        this.id = id;
        this.patientId = patientId;
        this.medecin = medecin;
        this.dateConsultation = dateConsultation;
        this.diagnostic = diagnostic;
        this.traitement = traitement;
        this.notes = notes;
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

    public LocalDateTime getDateConsultation() {
        return dateConsultation;
    }

    public void setDateConsultation(LocalDateTime dateConsultation) {
        this.dateConsultation = dateConsultation;
    }

    public String getDiagnostic() {
        return diagnostic;
    }

    public void setDiagnostic(String diagnostic) {
        this.diagnostic = diagnostic;
    }

    public String getTraitement() {
        return traitement;
    }

    public void setTraitement(String traitement) {
        this.traitement = traitement;
    }

    public String getNotes() {
        return notes;
    }

    public void setNotes(String notes) {
        this.notes = notes;
    }

    @Override
    public String toString() {
        return "Consultation{" +
                "id=" + id +
                ", patientId=" + patientId +
                ", dateConsultation=" + dateConsultation +
                '}';
    }
}

