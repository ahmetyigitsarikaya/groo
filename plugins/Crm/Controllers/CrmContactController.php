<?php

declare(strict_types=1);

namespace Plugins\Crm\Controllers;

use App\Core\Exceptions\ValidationException;
use Plugins\Admin\Controllers\BaseController;
use Plugins\Admin\Support\Flash;
use Plugins\Crm\Services\ActivityService;
use Plugins\Crm\Services\ContactService;
use Throwable;

final class CrmContactController extends BaseController
{
    private ContactService $contactService;

    private ActivityService $activityService;

    public function __construct(?ContactService $contactService = null, ?ActivityService $activityService = null)
    {
        parent::__construct();
        $this->contactService = $contactService ?? new ContactService();
        $this->activityService = $activityService ?? new ActivityService();
    }

    public function index(array $params = []): void
    {
        $contacts = $this->contactService->getAllContacts();

        $this->render('admin/crm/contacts/index', [
            'contacts' => $contacts,
        ]);
    }

    public function create(array $params = []): void
    {
        $this->render('admin/crm/contacts/form', [
            'contact' => null,
            'is_edit' => false,
        ]);
    }

    public function store(array $params = []): void
    {
        $this->requireCsrf();

        $data = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'type' => (string) ($_POST['type'] ?? 'b2b'),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'status' => (string) ($_POST['status'] ?? 'lead'),
            'tags' => $_POST['tags'] ?? '',
            'custom_fields' => $_POST['custom_fields'] ?? '{}',
        ];

        try {
            $id = $this->contactService->createContact($data);
            Flash::set('success', 'Müşteri kaydı başarıyla oluşturuldu.');
            $this->redirect('/admin/crm/contacts/' . $id);
        } catch (ValidationException $e) {
            Flash::set('error', 'Doğrulama hatası: ' . $e->getMessage());
            $this->render('admin/crm/contacts/form', [
                'contact' => $data,
                'is_edit' => false,
            ]);
        } catch (Throwable $e) {
            Flash::set('error', 'Hata oluştu: ' . $e->getMessage());
            $this->render('admin/crm/contacts/form', [
                'contact' => $data,
                'is_edit' => false,
            ]);
        }
    }

    public function show(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $contact = $this->contactService->getContactById($id);

        if ($contact === null) {
            Flash::set('error', 'Müşteri bulunamadı.');
            $this->redirect('/admin/crm/contacts');
        }

        $timeline = $this->contactService->getContactTimeline($id);

        $this->render('admin/crm/contacts/show', [
            'contact' => $contact,
            'timeline' => $timeline,
        ]);
    }

    public function edit(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $contact = $this->contactService->getContactById($id);

        if ($contact === null) {
            Flash::set('error', 'Müşteri bulunamadı.');
            $this->redirect('/admin/crm/contacts');
        }

        $this->render('admin/crm/contacts/form', [
            'contact' => $contact,
            'is_edit' => true,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireCsrf();

        $id = (int) ($params['id'] ?? 0);
        $contact = $this->contactService->getContactById($id);

        if ($contact === null) {
            Flash::set('error', 'Müşteri bulunamadı.');
            $this->redirect('/admin/crm/contacts');
        }

        $data = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'type' => (string) ($_POST['type'] ?? 'b2b'),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'status' => (string) ($_POST['status'] ?? 'lead'),
            'tags' => $_POST['tags'] ?? '',
            'custom_fields' => $_POST['custom_fields'] ?? '{}',
        ];

        try {
            $this->contactService->updateContact($id, $data);
            Flash::set('success', 'Müşteri bilgileri güncellendi.');
            $this->redirect('/admin/crm/contacts/' . $id);
        } catch (ValidationException $e) {
            Flash::set('error', 'Doğrulama hatası: ' . $e->getMessage());
            $data['id'] = $id;
            $this->render('admin/crm/contacts/form', [
                'contact' => $data,
                'is_edit' => true,
            ]);
        }
    }

    public function addNote(array $params): void
    {
        $this->requireCsrf();

        $id = (int) ($params['id'] ?? 0);
        $note = trim((string) ($_POST['note'] ?? ''));
        $type = (string) ($_POST['activity_type'] ?? 'note');

        if ($note !== '') {
            $this->activityService->addNote($id, $note, $type);
            Flash::set('success', 'Aktivite / Not eklendi.');
        } else {
            Flash::set('error', 'Not içeriği boş olamaz.');
        }

        $this->redirect('/admin/crm/contacts/' . $id);
    }

    public function destroy(array $params): void
    {
        $this->requireCsrf();

        $id = (int) ($params['id'] ?? 0);
        $this->contactService->deleteContact($id);

        Flash::set('success', 'Müşteri kaydı silindi.');
        $this->redirect('/admin/crm/contacts');
    }
}
