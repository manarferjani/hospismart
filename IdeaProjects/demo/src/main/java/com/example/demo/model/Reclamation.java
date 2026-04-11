package com.example.demo.domain;

import java.time.LocalDateTime;

public class Reclamation {
    private Integer id;
    private String titre;
    private String description;
    private LocalDateTime dateCreation;
    private String email;
    private String nomPatient;
    private String statut;
    private String categorie;
    private String priorite;
    private String etatMental;

    public Reclamation() {
    }

    public Reclamation(Integer id, String titre, String description, LocalDateTime dateCreation,
                       String email, String nomPatient, String statut, String categorie,
                       String priorite, String etatMental) {
        this.id = id;
        this.titre = titre;
        this.description = description;
        this.dateCreation = dateCreation;
        this.email = email;
        this.nomPatient = nomPatient;
        this.statut = statut;
        this.categorie = categorie;
        this.priorite = priorite;
        this.etatMental = etatMental;
    }

    public Integer getId() {
        return id;
    }

    public void setId(Integer id) {
        this.id = id;
    }

    public String getTitre() {
        return titre;
    }

    public void setTitre(String titre) {
        this.titre = titre;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public LocalDateTime getDateCreation() {
        return dateCreation;
    }

    public void setDateCreation(LocalDateTime dateCreation) {
        this.dateCreation = dateCreation;
    }

    public String getEmail() {
        return email;
    }

    public void setEmail(String email) {
        this.email = email;
    }

    public String getNomPatient() {
        return nomPatient;
    }

    public void setNomPatient(String nomPatient) {
        this.nomPatient = nomPatient;
    }

    public String getStatut() {
        return statut;
    }

    public void setStatut(String statut) {
        this.statut = statut;
    }

    public String getCategorie() {
        return categorie;
    }

    public void setCategorie(String categorie) {
        this.categorie = categorie;
    }

    public String getPriorite() {
        return priorite;
    }

    public void setPriorite(String priorite) {
        this.priorite = priorite;
    }

    public String getEtatMental() {
        return etatMental;
    }

    public void setEtatMental(String etatMental) {
        this.etatMental = etatMental;
    }

    @Override
    public String toString() {
        return "Reclamation{" +
                "id=" + id +
                ", titre='" + titre + '\'' +
                ", nomPatient='" + nomPatient + '\'' +
                ", statut='" + statut + '\'' +
                ", priorite='" + priorite + '\'' +
                '}';
    }
}

