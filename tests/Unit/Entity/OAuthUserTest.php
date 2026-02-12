<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use Bone\OAuth2\Entity\OAuthUser;
use Codeception\Test\Unit;
use Del\Entity\User;

class OAuthUserTest extends Unit
{
    public function testGetIdentifier()
    {
        $baseUser = $this->createMock(User::class);
        $baseUser->method('getId')->willReturn(456);
        
        $oauthUser = OAuthUser::createFromBaseUser($baseUser);
        
        $this->assertEquals(456, $oauthUser->getIdentifier());
    }

    public function testCreateFromBaseUser()
    {
        $baseUser = $this->createMock(User::class);
        $baseUser->method('getId')->willReturn(789);
        $baseUser->method('toArray')->willReturn(['id' => 789, 'email' => 'test@example.com']);
        
        $oauthUser = OAuthUser::createFromBaseUser($baseUser);
        
        $this->assertInstanceOf(OAuthUser::class, $oauthUser);
        $this->assertEquals(789, $oauthUser->getIdentifier());
    }

    public function testCreateFromBaseUserReturnsNewInstance()
    {
        $baseUser = $this->createMock(User::class);
        $baseUser->method('getId')->willReturn(123);
        $baseUser->method('toArray')->willReturn(['id' => 123]);
        
        $oauthUser1 = OAuthUser::createFromBaseUser($baseUser);
        $oauthUser2 = OAuthUser::createFromBaseUser($baseUser);
        
        $this->assertNotSame($oauthUser1, $oauthUser2);
    }
}
