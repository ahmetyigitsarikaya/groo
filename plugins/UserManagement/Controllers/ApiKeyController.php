<?php

declare(strict_types=1);

namespace Plugins\UserManagement\Controllers;

use App\Core\Http\ApiResponse;
use Plugins\UserManagement\Repositories\ApiKeyRepository;
use Plugins\UserManagement\Repositories\UserRepository;
use Plugins\Admin\Support\Flash;
use Throwable;

/**
 * ApiKeyController — Kullanıcı başına API anahtarı yönetimi.
 * Admin arayüzü: anahtar üretme, iptal etme, silme.
 * REST API: Bearer token doğrulama endpoint'i.
 */
final class ApiKeyController extends BaseController
{
    private ApiKeyRepository $apiKeys;
    private UserRepository $users;

    public function __construct()
    {
        parent::__construct();
        $this->apiKeys = new ApiKeyRepository();
        $this->users   = new UserRepository();
    }

    // ----------------------------------------------------------------
    //  Admin: Anahtar üret
    // ----------------------------------------------------------------

    public function generate(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $userId = (int) $params['user_id'];
        $label  = trim((string) ($_POST['label'] ?? ''));

        $user = $this->users->find($userId);
        if ($user === null) {
            Flash::set('error', 'Kullanıcı bulunamadı.');
            $this->redirect('/admin/users');
        }

        try {
            $key = $this->apiKeys->generate($userId, $label);
            Flash::set('success', 'API anahtarı oluşturuldu: <code>' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '</code> — Lütfen şimdi kopyalayın, bir daha gösterilmeyecek!');
        } catch (Throwable $e) {
            Flash::set('error', $e->getMessage());
        }

        $this->redirect('/admin/users/' . $userId . '/edit');
    }

    // ----------------------------------------------------------------
    //  Admin: Anahtar iptal / sil
    // ----------------------------------------------------------------

    public function revoke(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $userId = (int) $params['user_id'];
        $keyId  = (int) $params['key_id'];

        $this->apiKeys->revoke($keyId, $userId);
        Flash::set('success', 'API anahtarı iptal edildi.');
        $this->redirect('/admin/users/' . $userId . '/edit');
    }

    public function delete(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $userId = (int) $params['user_id'];
        $keyId  = (int) $params['key_id'];

        $this->apiKeys->delete($keyId, $userId);
        Flash::set('success', 'API anahtarı silindi.');
        $this->redirect('/admin/users/' . $userId . '/edit');
    }

    // ----------------------------------------------------------------
    //  REST API: Bearer token doğrulama
    // ----------------------------------------------------------------

    /**
     * GET /api/um/me
     * Authorization: Bearer <api_key>
     *
     * Diğer eklentiler bu endpoint'i örnek alarak kendi API endpoint'lerini yazabilir.
     */
    public function me(array $params): void
    {
        $key = $this->extractBearerToken();

        if ($key === null) {
            http_response_code(401);
            ApiResponse::error('Kimlik doğrulama başarısız. Authorization: Bearer <token> header gerekli.', 'unauthorized');
            return;
        }

        $keyRecord = $this->apiKeys->findByKey($key);
        if ($keyRecord === null) {
            http_response_code(401);
            ApiResponse::error('Geçersiz veya iptal edilmiş API anahtarı.', 'unauthorized');
            return;
        }

        $this->apiKeys->touchLastUsed($key);

        $user = $this->users->find((int) $keyRecord['user_id']);
        if ($user === null) {
            http_response_code(404);
            ApiResponse::error('Kullanıcı bulunamadı.', 'not_found');
            return;
        }

        $userRepo = new UserRepository();
        $roles = $userRepo->roles((int) $user['id']);

        ApiResponse::success([
            'id'       => (int) $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'status'   => $user['status'],
            'roles'    => array_map(static fn (array $r): array => [
                'id'    => (int) $r['id'],
                'name'  => $r['name'],
                'color' => $r['color'],
            ], $roles),
        ]);
    }

    private function extractBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if (strpos($header, 'Bearer ') === 0) {
            return trim(substr($header, 7));
        }

        return null;
    }
}
