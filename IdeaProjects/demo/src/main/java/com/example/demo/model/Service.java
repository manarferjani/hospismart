package com.example.demo.domain;

public class Service {
    private Integer id;
    private String nom;
    private String description;
    private String responsable;
    private Integer capacite;

    public Service() {
    }

    public Service(Integer id, String nom, String description, String responsable, Integer capacite) {
        this.id = id;
        this.nom = nom;
        this.description = description;
        this.responsable = responsable;
        this.capacite = capacite;
    }

    // Getters and Setters
    public Integer getId() {
        return id;
    }

    public void setId(Integer id) {
        this.id = id;
    }

    public String getNom() {
        return nom;
    }

    public void setNom(String nom) {
        this.nom = nom;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public String getResponsable() {
        return responsable;
    }

    public void setResponsable(String responsable) {
        this.responsable = responsable;
    }

    public Integer getCapacite() {
        return capacite;
    }

    public void setCapacite(Integer capacite) {
        this.capacite = capacite;
    }

    @Override
    public String toString() {
        return nom;
    }
}

