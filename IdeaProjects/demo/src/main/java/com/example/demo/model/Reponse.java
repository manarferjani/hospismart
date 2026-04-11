package com.example.demo.domain;

import java.time.LocalDateTime;

public class Reponse {
    private Integer id;
    private Integer reclamationId;
    private String contenuReponse;
    private LocalDateTime dateReponse;
    private String auteurReponse;
    private String statut;

    public Reponse() {
    }

    public Reponse(Integer id, Integer reclamationId, String contenuReponse, LocalDateTime dateReponse,
                   String auteurReponse, String statut) {
        this.id = id;
        this.reclamationId = reclamationId;
        this.contenuReponse = contenuReponse;
        this.dateReponse = dateReponse;
        this.auteurReponse = auteurReponse;
        this.statut = statut;
    }

    // Getters and Setters
    public Integer getId() {
        return id;
    }

    public void setId(Integer id) {
        this.id = id;
    }

    public Integer getReclamationId() {
        return reclamationId;
    }

    public void setReclamationId(Integer reclamationId) {
        this.reclamationId = reclamationId;
    }

    public String getContenuReponse() {
        return contenuReponse;
    }

    public void setContenuReponse(String contenuReponse) {
        this.contenuReponse = contenuReponse;
    }

    public LocalDateTime getDateReponse() {
        return dateReponse;
    }

    public void setDateReponse(LocalDateTime dateReponse) {
        this.dateReponse = dateReponse;
    }

    public String getAuteurReponse() {
        return auteurReponse;
    }

    public void setAuteurReponse(String auteurReponse) {
        this.auteurReponse = auteurReponse;
    }

    public String getStatut() {
        return statut;
    }

    public void setStatut(String statut) {
        this.statut = statut;
    }

    @Override
    public String toString() {
        return "Reponse{" +
                "id=" + id +
                ", reclamationId=" + reclamationId +
                ", dateReponse=" + dateReponse +
                ", statut='" + statut + '\'' +
                '}';
    }
}

