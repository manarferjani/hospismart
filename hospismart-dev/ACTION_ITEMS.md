# HospiSmart - Implementation Action Items

## 🚀 IMMEDIATE ACTION ITEMS

### Priority 1: Critical - Implement Missing Management Systems

#### 1.1 Create Campaign Management (Campagne)

**Files to Create**:
1. `src/Form/CampagneType.php`
2. `src/Controller/CampagneController.php`
3. `templates/campagne/index.html.twig`
4. `templates/campagne/new.html.twig`
5. `templates/campagne/edit.html.twig`
6. `templates/campagne/show.html.twig`
7. `templates/campagne/_form.html.twig`
8. `templates/campagne/_delete_form.html.twig`

**Form Fields** (CampagneType):
```
- titre (required, 3-255 chars)
- theme (required, 2-255 chars)
- description (required, 10-5000 chars)
- date_debut (required, must be future)
- date_fin (required, must be after start)
- budget (required, must be > 0)
```

**Controller Routes** (`#[Route('/campagne')]`):
- `app_campagne_index` (GET) - List all campaigns
- `app_campagne_new` (GET/POST) - Create campaign
- `app_campagne_show` (GET) - View campaign
- `app_campagne_edit` (GET/POST) - Edit campaign
- `app_campagne_delete` (POST) - Delete campaign

---

#### 1.2 Create Diagnostic Management (Diagnostic)

**Files to Create**:
1. `src/Form/DiagnosticType.php`
2. `src/Controller/DiagnosticController.php`
3. `templates/diagnostic/index.html.twig`
4. `templates/diagnostic/new.html.twig`
5. `templates/diagnostic/edit.html.twig`
6. `templates/diagnostic/show.html.twig`
7. `templates/diagnostic/_form.html.twig`
8. `templates/diagnostic/_delete_form.html.twig`

**Form Fields** (DiagnosticType):
```
- contenu (required, text, 10-5000 chars)
- probabilite_ia (required, float, 0-100)
```

**Controller Routes** (`#[Route('/diagnostic')]`):
- `app_diagnostic_index` (GET) - List diagnostics
- `app_diagnostic_new` (GET/POST) - Create diagnostic
- `app_diagnostic_show` (GET) - View diagnostic
- `app_diagnostic_edit` (GET/POST) - Edit diagnostic
- `app_diagnostic_delete` (POST) - Delete diagnostic

---

#### 1.3 Create Vital Parameters Management (ParametreVital)

**Files to Create**:
1. `src/Form/ParametreVitalType.php`
2. `src/Controller/ParametreVitalController.php`
3. `templates/parametre_vital/index.html.twig`
4. `templates/parametre_vital/new.html.twig`
5. `templates/parametre_vital/edit.html.twig`
6. `templates/parametre_vital/show.html.twig`
7. `templates/parametre_vital/_form.html.twig`
8. `templates/parametre_vital/_delete_form.html.twig`

**Form Fields** (ParametreVitalType):
```
- tension (required, regex format: XXX/XXX)
- temperature (required, float, 35-45°C)
- frequence_cardiaque (required, int, 40-200 bpm)
- date_prise (required, datetime, not future)
```

**Controller Routes** (`#[Route('/parametre-vital')]`):
- `app_parametre_vital_index` (GET) - List vital parameters
- `app_parametre_vital_new` (GET/POST) - Create vital parameter
- `app_parametre_vital_show` (GET) - View vital parameter
- `app_parametre_vital_edit` (GET/POST) - Edit vital parameter
- `app_parametre_vital_delete` (POST) - Delete vital parameter

---

### Priority 2: Important - Complete Incomplete Systems

#### 2.1 Complete Doctor Management

**Current Status**: Limited to profile view
**Required Changes**:

1. **Modify MedecinController** to add:
   - `app_medecin_index` (GET) - List doctors
   - `app_medecin_new` (GET/POST) - Create doctor
   - `app_medecin_edit` (GET/POST) - Edit doctor
   - `app_medecin_delete` (POST) - Delete doctor

2. **Update MedecinType form** to handle:
   - All doctor-specific fields
   - Service assignment

3. **Create Templates**:
   - `templates/medecin/index.html.twig`
   - `templates/medecin/new.html.twig`
   - `templates/medecin/edit.html.twig` (separate from show)

4. **Security**: Add `#[IsGranted('ROLE_ADMIN')]` to new CRUD methods

---

#### 2.2 Complete Appointment (RendezVous) Admin Management

**Current Status**: Patient-side only
**Required Changes**:

1. **Enhance RendezVousController** to add:
   - `app_rendezvous_index` (GET) - List all appointments (admin)
   - `app_rendezvous_admin_show` (GET) - View appointment details
   - `app_rendezvous_admin_edit` (GET/POST) - Edit appointment
   - `app_rendezvous_admin_delete` (POST) - Delete appointment

2. **Create Templates**:
   - `templates/rendezvous/admin_index.html.twig`
   - `templates/rendezvous/admin_show.html.twig`
   - `templates/rendezvous/admin_edit.html.twig`
   - `templates/rendezvous/_admin_delete_form.html.twig`

3. **Security**: Wrap new admin routes with role checks

---

#### 2.3 Complete Complaint (Reclamation) Management

**Current Status**: Create and respond only
**Required Changes**:

1. **Enhance BackOfficeController** or create ReclamationController:
   - `app_reclamation_index` (or update existing) 
   - `app_reclamation_edit` (GET/POST) - Edit complaint
   - `app_reclamation_delete` (POST) - Delete complaint

2. **Create Templates**:
   - `templates/back_office/edit_reclamation.html.twig` (or `reclamation/edit.html.twig`)
   - `templates/back_office/_reclamation_delete_form.html.twig`

3. **Features**:
   - Status change (En attente → En cours → Traité)
   - Priority adjustment
   - Category modification

---

#### 2.4 Implement Notification UI

**Current Status**: No user-facing UI
**Required Components**:

1. **Create NotificationController**:
   - `app_notification_index` (GET) - List notifications
   - `app_notification_mark_read` (POST) - Mark as read
   - `app_notification_delete` (POST) - Delete notification
   - `app_notification_mark_all_read` (POST) - Mark all as read

2. **Create Templates**:
   - `templates/notification/index.html.twig` - Notification center
   - `templates/notification/_notification_item.html.twig` - Single notification

3. **Add to Base Layout**:
   - Notification badge in header
   - Notification dropdown menu

4. **Routes**:
   - `#[Route('/notifications')]` prefix

---

### Priority 3: Enhancements

#### 3.1 Improve Admin Dashboard

**Current Status**: Basic complaint overview
**Enhancements Needed**:

1. Add tabs for different statistics:
   - Appointment statistics
   - Doctor activity
   - Patient statistics
   - System health

2. Create new dashboard sections:
   - `templates/back/dashboard/appointments.html.twig`
   - `templates/back/dashboard/doctors.html.twig`
   - `templates/back/dashboard/patients.html.twig`
   - `templates/back/dashboard/system.html.twig`

---

#### 3.2 Complete User Management in Admin Panel

**Current Status**: Partial UtilisateurCrudController
**To Complete**:

1. Verify `templates/back/utilisateurs/list.html.twig` exists
2. Create/verify: `templates/back/utilisateurs/new.html.twig`
3. Add: `templates/back/utilisateurs/edit.html.twig` (if able to edit)
4. Add: `templates/back/utilisateurs/show.html.twig`

---

#### 3.3 Add Missing API Endpoints

**Currently Implemented**:
- `/api/evenements/prochains` - Get upcoming events
- `/api/evenements/{id}` - Event details
- `/api/evenements` - List events
- `/api/stock/faible` - Low stock items

**Should Add**:
- `/api/medicament` - Full medicine CRUD
- `/api/consultation` - Consultation endpoints
- `/api/rendezvous` - Appointment endpoints
- `/api/users` - User statistics
- `/api/services` - Service list

---

## 📝 IMPLEMENTATION CHECKLIST

### Phase 1: Critical (Week 1)
- [ ] Create CampagneType form
- [ ] Create CampagneController with full CRUD
- [ ] Create Campagne templates (8 files)
- [ ] Test Campaign management
- [ ] Create DiagnosticType form
- [ ] Create DiagnosticController with full CRUD
- [ ] Create Diagnostic templates (8 files)
- [ ] Test Diagnostic management
- [ ] Create ParametreVitalType form
- [ ] Create ParametreVitalController with full CRUD
- [ ] Create ParametreVital templates (8 files)
- [ ] Test Vital Parameters management

### Phase 2: Important (Week 2)
- [ ] Complete MedecinController CRUD
- [ ] Create MedecinType enhancements
- [ ] Update Medecin templates
- [ ] Test Doctor management
- [ ] Add RendezVous admin interface
- [ ] Update RendezVous templates
- [ ] Test Appointment management
- [ ] Complete Reclamation editing
- [ ] Create Reclamation edit templates
- [ ] Test Complaint management

### Phase 3: Enhance (Week 3)
- [ ] Implement NotificationController
- [ ] Create Notification templates
- [ ] Add notification badge to layout
- [ ] Test Notification UI
- [ ] Improve Admin Dashboard
- [ ] Complete User Management templates
- [ ] Test Admin Panel

### Phase 4: API & Polish (Week 4)
- [ ] Add missing API endpoints
- [ ] Test all API endpoints
- [ ] Security audit
- [ ] Performance optimization
- [ ] Testing coverage

---

## 🔧 TECHNICAL GUIDELINES

### Form Creation Template
```php
<?php

namespace App\Form;

use App\Entity\YourEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class YourEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('field1', TextType::class, [
                'label' => 'Field Label',
                'data_class' => null,
                'attr' => ['class' => 'form-control']
            ])
            ->add('field2', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => YourEntity::class,
        ]);
    }
}
```

### Controller Creation Template
```php
<?php

namespace App\Controller;

use App\Entity\YourEntity;
use App\Form\YourEntityType;
use App\Repository\YourEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/your-entity')]
final class YourEntityController extends AbstractController
{
    #[Route(name: 'app_your_entity_index', methods: ['GET'])]
    public function index(YourEntityRepository $repository): Response
    {
        return $this->render('your_entity/index.html.twig', [
            'entities' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_your_entity_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $entity = new YourEntity();
        $form = $this->createForm(YourEntityType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($entity);
            $entityManager->flush();

            return $this->redirectToRoute('app_your_entity_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('your_entity/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_your_entity_show', methods: ['GET'])]
    public function show(YourEntity $entity): Response
    {
        return $this->render('your_entity/show.html.twig', [
            'entity' => $entity,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_your_entity_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, YourEntity $entity, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(YourEntityType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_your_entity_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('your_entity/edit.html.twig', [
            'entity' => $entity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_your_entity_delete', methods: ['POST'])]
    public function delete(Request $request, YourEntity $entity, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($entity);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_your_entity_index', [], Response::HTTP_SEE_OTHER);
    }
}
```

### Template Structure (index.html.twig)
```twig
{% extends 'base.html.twig' %}

{% block title %}Your Entities{% endblock %}

{% block content %}
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Your Entities</h1>
        <a href="{{ path('app_your_entity_new') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Entity
        </a>
    </div>

    {% if entities %}
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Field 1</th>
                    <th>Field 2</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                {% for entity in entities %}
                    <tr>
                        <td>{{ entity.field1 }}</td>
                        <td>{{ entity.field2 }}</td>
                        <td>
                            <a href="{{ path('app_your_entity_show', {id: entity.id}) }}" class="btn btn-sm btn-info">View</a>
                            <a href="{{ path('app_your_entity_edit', {id: entity.id}) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form method="post" action="{{ path('app_your_entity_delete', {id: entity.id}) }}" style="display:inline;">
                                <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ entity.id) }}">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                {% endfor %}
            </tbody>
        </table>
    {% else %}
        <div class="alert alert-info">No entities found. <a href="{{ path('app_your_entity_new') }}">Create one</a></div>
    {% endif %}
</div>
{% endblock %}
```

---

## 📊 TESTING CHECKLIST

For each new feature, test:

- [ ] **Create**: Can create new entity via form
- [ ] **List**: All entities displayed with pagination (if many)
- [ ] **View**: Can view individual entity details
- [ ] **Edit**: Can edit and save changes
- [ ] **Delete**: Can delete with confirmation
- [ ] **Validation**: Form validation works (required fields, formats)
- [ ] **Authorization**: Only authorized users can access
- [ ] **Error Handling**: Proper error messages displayed
- [ ] **Flash Messages**: Success/error messages shown
- [ ] **Redirect**: Correct redirects after actions

---

## 🔒 SECURITY CHECKLIST

For each controller, ensure:

- [ ] CSRF protection on form submissions
- [ ] Role-based access control (if needed)
- [ ] Input validation
- [ ] SQL injection prevention (using Doctrine)
- [ ] XSS prevention (Twig auto-escapes)
- [ ] HTTPS for sensitive operations
- [ ] Audit logging for admin actions

---

## 🎨 UI IMPROVEMENT OPPORTUNITIES

1. **Add breadcrumbs** to navigation
2. **Improve form layouts** with Bootstrap classes
3. **Add modal confirmations** for deletions
4. **Implement pagination** for large lists
5. **Add search/filter** to list views
6. **Use icons** for action buttons
7. **Color-code statuses** (red/yellow/green)
8. **Add tooltips** for help text
9. **Responsive design** for mobile

---

## 📚 RESOURCES

### Symfony Functions Used Frequently in Project
- `$this->render()` - Render template
- `$this->redirectToRoute()` - Redirect to route
- `$this->addFlash()` - Add flash message
- `$this->denyAccessUnlessGranted()` - Check permissions
- `$this->getUser()` - Get current user
- `$this->createForm()` - Create form instance
- `$this->isCsrfTokenValid()` - Validate CSRF token

### Common Twig Variables/Filters
- `{{ entity.field }}` - Display property
- `{% if condition %}` - Conditional rendering
- `{% for item in items %}` - Loop through array
- `{{ 'string'|length }}` - Filter example
- `{{ date|date('Y-m-d') }}` - Format date
- `{{ path('route_name') }}` - Generate URL
- `{{ csrf_token('delete' ~ id) }}` - Get CSRF token

---

## 🚦 COMPLETION STATUS TARGETS

After completing all action items:

- **Core Features**: 100% complete
- **Management Screens**: 95% complete
- **API Integration**: 60% complete
- **Front Office**: 75% complete
- **Admin Panel**: 85% complete

**Target Overall Project Completion: 90%**
