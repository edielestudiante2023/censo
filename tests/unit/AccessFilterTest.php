<?php

use App\Filters\AuthFilter;
use App\Filters\RoleFilter;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AccessFilterTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        session()->remove(['isLoggedIn', 'rol']);

        parent::tearDown();
    }

    public function testAuthFilterRedirectsGuestsToLogin(): void
    {
        session()->remove(['isLoggedIn', 'rol']);

        $response = (new AuthFilter())->before(service('request'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/login', $response->getHeaderLine('Location'));
    }

    public function testAuthFilterAllowsAuthenticatedUsers(): void
    {
        session()->set('isLoggedIn', true);

        $this->assertNull((new AuthFilter())->before(service('request')));
    }

    public function testRoleFilterRedirectsGuestsToLogin(): void
    {
        session()->remove(['isLoggedIn', 'rol']);

        $response = (new RoleFilter())->before(service('request'), ['superadmin']);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/login', $response->getHeaderLine('Location'));
    }

    public function testRoleFilterRedirectsUsersWithoutRequiredRole(): void
    {
        session()->set([
            'isLoggedIn' => true,
            'rol'        => 'cliente',
        ]);

        $response = (new RoleFilter())->before(service('request'), ['superadmin', 'admin']);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/dashboard', $response->getHeaderLine('Location'));
    }

    public function testRoleFilterAllowsUsersWithRequiredRole(): void
    {
        session()->set([
            'isLoggedIn' => true,
            'rol'        => 'admin',
        ]);

        $this->assertNull((new RoleFilter())->before(service('request'), ['superadmin', 'admin']));
    }
}
