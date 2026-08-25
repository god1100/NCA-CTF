<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

final class ParticipantService
{
    private const VALID_STATUSES = [
        'active',
        'inactive',
        'suspended',
    ];

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private readonly UserRepository $users,
        private readonly AuditLogger $audit
    ) {
    }

    /**
     * Return a paginated list of participants/users.
     *
     * @return array{
     *     success: bool,
     *     participants?: array<int, array<string, mixed>>,
     *     pagination?: array<string, int>,
     *     error_code?: string,
     *     errors?: array<int, string>
     * }
     */
    public function list(
        int $page = 1,
        int $perPage = 20,
        ?string $search = null,
        ?string $status = null
    ): array {
        $page = max(1, $page);
        $perPage = min(
            self::MAX_PER_PAGE,
            max(1, $perPage)
        );

        /*
         * Validate status filter.
         */
        if ($status !== null && $status !== '') {
            $status = strtolower(trim($status));

            if (!in_array($status, self::VALID_STATUSES, true)) {
                return [
                    'success' => false,
                    'error_code' => 'INVALID_STATUS',
                    'errors' => [
                        'Invalid status filter.'
                    ],
                ];
            }
        } else {
            $status = null;
        }

        /*
         * Normalize search input.
         */
        if ($search !== null) {
            $search = trim($search);

            if ($search === '') {
                $search = null;
            }

            /*
             * Avoid unnecessarily huge search strings.
             */
            if ($search !== null) {
                $search = substr($search, 0, 100);
            }
        }

        $result = $this->users->paginateParticipants(
            $page,
            $perPage,
            $search,
            $status
        );

        $total = (int) $result['total'];

        return [
            'success' => true,

            'participants' => array_map(
                static function (array $user): array {
                    return [
                        'id' => (int) $user['id'],
                        'username' => (string) $user['username'],
                        'email' => (string) $user['email'],
                        'full_name' => $user['full_name'] !== null
                            ? (string) $user['full_name']
                            : null,
                        'role_id' => (int) $user['role_id'],
                        'role' => $user['role'] !== null
                            ? (string) $user['role']
                            : null,
                        'status' => (string) $user['status'],
                        'created_at' => $user['created_at'],
                        'last_login_at' => $user['last_login_at'] ?? null,
                    ];
                },
                $result['items']
            ),

            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $perPage > 0
                    ? (int) ceil($total / $perPage)
                    : 0,
            ],
        ];
    }

    /**
     * Get a single participant/user.
     *
     * @return array{
     *     success: bool,
     *     participant?: array<string, mixed>,
     *     error_code?: string,
     *     errors?: array<int, string>
     * }
     */
    public function show(int $userId): array
    {
        if ($userId <= 0) {
            return [
                'success' => false,
                'error_code' => 'INVALID_ID',
                'errors' => [
                    'Invalid participant ID.'
                ],
            ];
        }

        $user = $this->users->findParticipantById($userId);

        if ($user === null) {
            return [
                'success' => false,
                'error_code' => 'NOT_FOUND',
                'errors' => [
                    'Participant not found.'
                ],
            ];
        }

        return [
            'success' => true,
            'participant' => [
                'id' => (int) $user['id'],
                'username' => (string) $user['username'],
                'email' => (string) $user['email'],
                'full_name' => $user['full_name'] !== null
                    ? (string) $user['full_name']
                    : null,
                'role_id' => (int) $user['role_id'],
                'role' => $user['role'] !== null
                    ? (string) $user['role']
                    : null,
                'status' => (string) $user['status'],
                'created_at' => $user['created_at'],
                'last_login_at' => $user['last_login_at'] ?? null,
            ],
        ];
    }

    /**
     * Change a participant's account status.
     *
     * @return array{
     *     success: bool,
     *     participant?: array<string, mixed>,
     *     error_code?: string,
     *     errors?: array<int, string>
     * }
     */
    public function updateStatus(
        array $actingUser,
        int $userId,
        string $status,
        string $ip
    ): array {
        if ($userId <= 0) {
            return [
                'success' => false,
                'error_code' => 'INVALID_ID',
                'errors' => [
                    'Invalid participant ID.'
                ],
            ];
        }

        $status = strtolower(trim($status));

        if (!in_array($status, self::VALID_STATUSES, true)) {
            return [
                'success' => false,
                'error_code' => 'INVALID_STATUS',
                'errors' => [
                    'Status must be one of: '
                    . implode(', ', self::VALID_STATUSES)
                    . '.',
                ],
            ];
        }

        $participant = $this->users->findParticipantById($userId);

        if ($participant === null) {
            return [
                'success' => false,
                'error_code' => 'NOT_FOUND',
                'errors' => [
                    'Participant not found.'
                ],
            ];
        }

        $actingUserId = isset($actingUser['id'])
            ? (int) $actingUser['id']
            : 0;

        /*
         * Prevent an administrator from disabling/suspending
         * their own currently authenticated account.
         */
        if ($actingUserId === $userId && $status !== 'active') {
            return [
                'success' => false,
                'error_code' => 'SELF_ACTION_NOT_ALLOWED',
                'errors' => [
                    'You cannot deactivate or suspend your own account.'
                ],
            ];
        }

        $oldStatus = (string) $participant['status'];

        /*
         * Nothing to update.
         */
        if ($oldStatus === $status) {
            return [
                'success' => true,
                'participant' => $this->shapeParticipant($participant),
            ];
        }

        $updated = $this->users->updateStatus(
            $userId,
            $status
        );

        if (!$updated) {
            return [
                'success' => false,
                'error_code' => 'UPDATE_FAILED',
                'errors' => [
                    'Unable to update participant status.'
                ],
            ];
        }

        /*
         * Audit the administrative action.
         */
        $this->audit->log(
            'PARTICIPANT_STATUS_CHANGED',
            $actingUserId > 0 ? $actingUserId : null,
            [
                'participant_id' => $userId,
                'username' => $participant['username'],
                'from' => $oldStatus,
                'to' => $status,
            ],
            \App\Infrastructure\Hash::correlate($ip),
            'user',
            $userId
        );

        $updatedParticipant =
            $this->users->findParticipantById($userId);

        return [
            'success' => true,
            'participant' => $updatedParticipant !== null
                ? $this->shapeParticipant($updatedParticipant)
                : null,
        ];
    }

    /**
     * Permanently delete a participant.
     *
     * @return array{
     *     success: bool,
     *     error_code?: string,
     *     errors?: array<int, string>
     * }
     */
    public function delete(
        array $actingUser,
        int $userId,
        string $ip
    ): array {
        if ($userId <= 0) {
            return [
                'success' => false,
                'error_code' => 'INVALID_ID',
                'errors' => [
                    'Invalid participant ID.'
                ],
            ];
        }

        $participant = $this->users->findParticipantById($userId);

        if ($participant === null) {
            return [
                'success' => false,
                'error_code' => 'NOT_FOUND',
                'errors' => [
                    'Participant not found.'
                ],
            ];
        }

        $actingUserId = isset($actingUser['id'])
            ? (int) $actingUser['id']
            : 0;

        /*
         * Never allow an administrator to delete themselves.
         */
        if ($actingUserId === $userId) {
            return [
                'success' => false,
                'error_code' => 'SELF_ACTION_NOT_ALLOWED',
                'errors' => [
                    'You cannot delete your own account.'
                ],
            ];
        }

        /*
         * Protect administrator accounts from deletion.
         *
         * Participant management should not become a way for one
         * admin to accidentally destroy another administrator.
         */
        $role = (string) ($participant['role'] ?? '');

        if (in_array(
            $role,
            ['challenge_admin', 'super_admin'],
            true
        )) {
            return [
                'success' => false,
                'error_code' => 'ADMIN_DELETE_NOT_ALLOWED',
                'errors' => [
                    'Administrator accounts cannot be deleted from participant management.'
                ],
            ];
        }

        /*
         * Delete the user.
         */
        $deleted = $this->users->deleteById($userId);

        if (!$deleted) {
            return [
                'success' => false,
                'error_code' => 'DELETE_FAILED',
                'errors' => [
                    'Unable to delete participant.'
                ],
            ];
        }

        /*
         * Audit before the database record disappears completely
         * from the users table.
         */
        $this->audit->log(
            'PARTICIPANT_DELETED',
            $actingUserId > 0 ? $actingUserId : null,
            [
                'participant_id' => $userId,
                'username' => $participant['username'],
                'email' => $participant['email'],
            ],
            \App\Infrastructure\Hash::correlate($ip),
            'user',
            $userId
        );

        return [
            'success' => true,
        ];
    }

    /**
     * Convert a database row into the API representation.
     */
    private function shapeParticipant(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'email' => (string) $user['email'],
            'full_name' => $user['full_name'] !== null
                ? (string) $user['full_name']
                : null,
            'role_id' => (int) $user['role_id'],
            'role' => $user['role'] !== null
                ? (string) $user['role']
                : null,
            'status' => (string) $user['status'],
            'created_at' => $user['created_at'],
            'last_login_at' => $user['last_login_at'] ?? null,
        ];
    }
}