# HospiSmart - Quick Reference Guide

## 🎯 ROUTES QUICK REFERENCE

### Patient Management
**Route Prefix**: `/patient`
| Route | Method | Purpose |
|-------|--------|---------|
| `/` | GET | List all patients |
| `/new` | GET/POST | Create patient |
| `/{id}` | GET | View patient |
| `/{id}/edit` | GET/POST | Edit patient |
| `/{id}` | POST | Delete patient |
| `/recherche-patient` | GET | Search doctors |
| `/mes-coordonnees` | GET | My coordinates |

### Medecin (Doctor) Management
**Route Prefix**: `/medecin`
| Route | Method | Purpose |
|-------|--------|---------|
| `/dashboard` | GET | Doctor dashboard |
| `/demandes-rendezvous` | GET | Appointment requests |
| `/mes-patients` | GET | My patients list |
| `/mon-profil` | GET | My profile |
| `/mes-dispos/gestion` | GET | Manage availability |

### Consultation Management
**Route Prefix**: `/consultation`
| Route | Method | Purpose |
|-------|--------|---------|
| `/` | GET | List consultations |
| `/new` | GET/POST | Create consultation |
| `/{id}` | GET | View consultation |
| `/{id}/edit` | GET/POST | Edit consultation |
| `/{id}` | POST | Delete consultation |

### Appointment Management
**Route Prefix**: `/rendezvous`
| Route | Method | Purpose |
|-------|--------|---------|
| `/dispo/{id}` | GET | View doctor availability |
| `/reserver/{id}` | GET/POST | Book appointment |
| `/annuler/{id}` | POST/GET | Cancel appointment |
| `/mes-rendezvous` | GET | My appointments |
| `/medecin/rendezvous/{id}/accepter` | POST | Accept appointment |
| `/medecin/rendezvous/{id}/refuser` | POST | Refuse appointment |

### Service Management
**Route Prefix**: `/service`
| Route | Method | Purpose |
|-------|--------|---------|
| `/` | GET | List services |
| `/new` | GET/POST | Create service |
| `/{id}` | GET | View service |
| `/{id}/edit` | GET/POST | Edit service |
| `/{id}` | POST | Delete service |

### Equipment Management
**Route Prefix**: `/equipement`
| Route | Method | Purpose |
|-------|--------|---------|
| `/` | GET | List equipment |
| `/new` | GET/POST | Create equipment |
| `/{id}` | GET | View equipment |
| `/{id}/edit` | GET/POST | Edit equipment |
| `/{id}` | POST | Delete equipment |

### Medicine Management
**Route Prefix**: `/medicament`
| Route | Method | Purpose |
|-------|--------|---------|
| `/` | GET | List medicines |
| `/new` | GET/POST | Create medicine |
| `/{id}` | GET | View medicine |
| `/{id}/edit` | GET/POST | Edit medicine |
| `/{id}` | POST | Delete medicine |
| `/export/pdf` | GET | Export to PDF |

### Stock Movement Management
**Route Prefix**: `/mouvement/stock`
| Route | Method | Purpose |
|-------|--------|---------|
| `/` | GET | List movements |
| `/new` | GET/POST | Create movement |
| `/{id}` | GET | View movement |
| `/{id}/edit` | GET/POST | Edit movement |
| `/{id}` | POST | Delete movement |

### Event Management
**Route Prefix**: `/evenement`
| Route | Method | Purpose |
|-------|--------|---------|
| `/` | GET | List events |
| `/public` | GET | Public events |
| `/new` | GET/POST | Create event |
| `/{id}` | GET | View event |
| `/{id}/edit` | GET/POST | Edit event |
| `/{id}` | POST | Delete event |

### Event Participant Management
**Route Prefix**: `/participant/evenement`
| Route | Method | Purpose |
|-------|--------|---------|
| `/` | GET | List participants |
| `/{id}` | GET | View participant |
| `/{id}` | POST | Delete participant |

### Admin - Complaints Management
**Route Prefix**: `/admin`
| Route | Method | Purpose |
|-------|--------|---------|
| `/` | GET | Dashboard |
| `/reclamation/{id}` | GET | View complaint |
| `/reclamation/{id}/repondre` | GET/POST | Respond to complaint |

### Admin - Responses Management
**Route Prefix**: `/admin/reponse`
| Route | Method | Purpose |
|-------|--------|---------|
| `/` | GET | List responses |
| `/new` | GET/POST | Create response |
| `/{id}` | GET | View response |
| `/{id}/edit` | GET/POST | Edit response |
| `/{id}` | POST | Delete response |

### Admin - Dashboard
**Route Prefix**: `/dashboard`
| Route | Method | Purpose |
|-------|--------|---------|
| `` | GET | Main dashboard |
| `/reclamations` | GET | Reclamations view |
| `/utilisateurs` | GET | Users list |
| `/utilisateurs/nouveau` | GET/POST | Create user |

### Authentication
| Route | Method | Purpose |
|-------|--------|---------|
| `/login` | GET/POST | Login |
| `/logout` | GET | Logout |
| `/register` | GET/POST | Register |

### Account
| Route | Method | Purpose |
|-------|--------|---------|
| `/mon-compte` | GET/POST | Edit my account |

### Front Office - Complaints
**Route Prefix**: `/front`
| Route | Method | Purpose |
|-------|--------|---------|
| `/` | GET | Front office index |
| `/reclamation/nouvelle` | GET/POST | New complaint |

### Front Office - Events
**Route Prefix**: `/evenement/public`
| Route | Method | Purpose |
|-------|--------|---------|
| `/{id}/inscription` | GET/POST | Register for event |

### Front Office - Reservations
**Route Prefix**: `/mes-reservations`
| Route | Method | Purpose |
|-------|--------|---------|
| `` | GET | My reservations |
| `/{id}/modifier` | GET/POST | Modify reservation |
| `/{id}/annuler` | POST | Cancel reservation |

### API Endpoints
**Route Prefix**: `/api`
| Route | Method | Purpose |
|-------|--------|---------|
| `/evenements/prochains` | GET | Next events (JSON) |
| `/evenements/{id}` | GET | Event details (JSON) |
| `/evenements` | GET | List events (JSON) |
| `/stock/faible` | GET | Low stock items (JSON) |

---

## 📋 FORMS REFERENCE

| Form Class | Entity | Fields Typically Handled |
|-----------|--------|------------------------|
| PatientType | User (PATIENT) | nom, prenom, email, tel, genre, date_naissance, groupe_sanguin, adresse |
| MedecinType | User (MEDECIN) | nom, prenom, email, tel, specialite, matricule, service |
| ServiceType | Service | nom, description |
| EquipementType | Equipement | name, description, service |
| MedicamentType | Medicament | nom, description, quantite, prix_unitaire, seuil_alerte |
| ConsultationType | Consultation | date_heure, motif, observations, patient, medecin, statut |
| RendezVousType | RendezVous | datetime, motif, statut, patient, medecin |
| DisponibiliteType | Disponibilite | date_debut, date_fin, medecin |
| EvenementType | Evenement | titre, description, type, date_debut, date_fin, lieu, statut, budget |
| ParticipantEvenementType | ParticipantEvenement | nom, prenom, email, tel, role |
| MouvementStockType | MouvementStock | medicament, type, quantite, description, date_mouvement |
| ReclamationType | Reclamation | titre, description, email, nomPatient, categorie, priorite |
| ReponseType | Reponse | contenu, reclamation |
| InscriptionParticipantType | ParticipantEvenement | Special form for event registration |
| ModifierMaReservationType | ParticipantEvenement | Specialized form for editing reservation |

**Missing Forms**:
- ❌ CampagneType
- ❌ DiagnosticType
- ❌ ParametreVitalType

---

## 🗂️ TEMPLATE STRUCTURE

### Base Templates
- `base.html.twig` - Main layout
- `base_admin.html.twig` - Admin layout
- `base_back.html.twig` - Back office layout
- `base_simple.html.twig` - Simple layout
- `front.html.twig` - Front office base

### Form Partials (Used by Multiple CRUD)
- `form/_form.html.twig` - General form template
- `form/` - Form components directory

### View Controllers Template Roots
- `patient/` - Patient CRUD views
- `medecin/` - Doctor views
- `consultation/` - Consultation views
- `rendezvous/` - Appointment workflow
- `service/` - Service CRUD views
- `equipement/` - Equipment CRUD views
- `medicament/` - Medicine CRUD views
- `mouvement_stock/` - Stock movement CRUD
- `evenement/` - Event CRUD views
- `participant_evenement/` - Event participant views
- `reponse/` - Response CRUD views
- `back_office/` - Admin complaint management
- `front_office/` - Front office complaint management
- `back/` - Admin dashboard sections
  - `back/dashboard/` - Dashboard views
  - `back/utilisateurs/` - User management
  - `back/compte/` - Account management

### Missing Template Directories
- ❌ `campagne/` - No campaign management UI
- ❌ `diagnostic/` - No diagnostic management UI
- ❌ `parametre_vital/` - No vital parameters UI

---

## 🔑 KEY ENTITIES AND RELATIONSHIPS

### User Entity
- **Type field**: Discriminator for PATIENT, MEDECIN, ADMIN
- **Relations**:
  - `rendezVousPatient` (OneToMany) - Appointments as patient
  - `rendezVousMedecin` (OneToMany) - Appointments as doctor
  - `disponibilites` (OneToMany) - Doctor availability
  - `notifications` (OneToMany) - Notifications received
  - `service_entity` (ManyToOne) - Service assignment (Medecins only)

### Service Entity
- Contains equipment and doctors
- Relations:
  - `equipements` (OneToMany)
  - `medecins` (OneToMany + filtering)

### RendezVous Entity
- Relationships:
  - `patient` (ManyToOne User)
  - `medecin` (ManyToOne User)
  - `disponibilite` (OneToOne)

### Consultation Entity
- Relationships:
  - `patient` (ManyToOne User)
  - `medecin` (ManyToOne User)
  - `rendezVous` (OneToOne)

### Evenement Entity
- Relationships:
  - `participants` (OneToMany ParticipantEvenement)
  - `createur` (ManyToOne User)

### Reclamation Entity
- Relationships:
  - `reponses` (OneToMany Reponse)

### Reponse Entity
- Relationships:
  - `reclamation` (ManyToOne)

---

## ✅ WHAT'S WORKING

### Fully Functional Management Areas
1. **Patient Management** - Complete CRUD
2. **Service Management** - Complete CRUD
3. **Equipment Management** - Complete CRUD
4. **Medicine Management** - Complete CRUD + PDF export
5. **Consultation Management** - Complete CRUD
6. **Event Management** - Complete CRUD
7. **Stock Movement** - Complete CRUD
8. **Response Management** - Complete CRUD
9. **Complaint Filing** - Front office only
10. **Appointment Booking** - Patient side workflow
11. **Doctor Dashboard** - Basic stats and activity
12. **Admin Dashboard** - Complaint overview

### Partially Working Areas
1. **Doctor Management** - Profile view only, no admin create/edit
2. **Appointment Management** - Booking works, no admin interface
3. **Complaint Management** - Can file and respond, cannot edit/delete
4. **Event Participation** - Registration works, limited management

---

## ❌ WHAT'S MISSING

### Complete Management Systems Not Implemented
1. **Campaigns** - Entity exists but no UI
2. **Diagnostics** - Entity exists but no UI
3. **Vital Parameters** - Entity exists but no UI

### Incomplete Systems
1. **Doctor CRUD** - Missing create/edit admin interface
2. **Admin Appointment Management** - No listing or editing
3. **Notification Management** - No user-facing notification system
4. **Complaint Editing** - Cannot modify existing complaints

---

## 🔍 ENTITY STATUS SUMMARY

### Entities with Complete UI
| Entity | Controller | Form | Status |
|--------|-----------|------|--------|
| Patient | ✅ | ✅ | 100% |
| Service | ✅ | ✅ | 100% |
| Equipement | ✅ | ✅ | 100% |
| Medicament | ✅ | ✅ | 100% |
| Consultation | ✅ | ✅ | 100% |
| Evenement | ✅ | ✅ | 100% |
| MouvementStock | ✅ | ✅ | 100% |
| Reponse | ✅ | ✅ | 100% |

### Entities with Partial UI
| Entity | Status | Missing |
|--------|--------|---------|
| User (Medecin) | ~60% | Create/Edit admin interface |
| RendezVous | ~40% | Admin list/edit interface |
| ParticipantEvenement | ~70% | Admin CRUD |
| Reclamation | ~70% | Edit/Delete functionality |
| Disponibilite | ~30% | List/Management interface |

### Entities with NO UI
| Entity | Status | Required |
|--------|--------|----------|
| Campagne | 0% | Full CRUD |
| Diagnostic | 0% | Full CRUD |
| ParametreVital | 0% | Full CRUD |
| Notification | 0% | Display interface |

---

## 📊 PROJECT METRICS

- **Total Controllers**: 20+
- **Total Routes**: 100+
- **Total Forms**: 14
- **Total Templates**: 60+
- **Total Entities**: 17
- **Total Repositories**: 20

### Completion Status
- **Core Features**: ~80% complete
- **Management Screens**: ~70% complete
- **API Integration**: ~30% complete
- **Front Office**: ~60% complete
- **Admin Panel**: ~50% complete

**Overall Project Completion: ~65%**
