<?php

namespace Auth;

use Event\AuthEvent;

/**
 * Github backend
 *
 * @package auth
 */
class Github extends Base
{
    /**
     * Backend name
     *
     * @var string
     */
    const AUTH_NAME = 'Github';

    /**
     * OAuth2 instance
     *
     * @access private
     * @var \Core\OAuth2
     */
    private $service;

    /**
     * Authenticate a Github user
     *
     * @access public
     * @param  string  $github_id   Github user id
     * @return boolean
     */
    public function authenticate($github_id)
    {
        $user = $this->user->getByGithubId($github_id);

        if (! empty($user)) {
            $this->userSession->refresh($user);
            $this->container['dispatcher']->dispatch('auth.success', new AuthEvent(self::AUTH_NAME, $user['id']));
            return true;
        }

        return false;
    }

    /**
     * Unlink a Github account for a given user
     *
     * @access public
     * @param  integer   $user_id    User id
     * @return boolean
     */
    public function unlink($user_id)
    {
        return $this->user->update(array(
            'id' => $user_id,
            'github_id' => '',
        ));
    }

    /**
     * Update the user table based on the Github profile information
     *
     * @access public
     * @param  integer   $user_id    User id
     * @param  array     $profile    Github profile
     * @return boolean
     */
    public function updateUser($user_id, array $profile)
    {
        $user = $this->user->getById($user_id);

        return $this->user->update(array(
            'id' => $user_id,
            'github_id' => $profile['id'],
            'email' => $profile['email'] ?: $user['email'],
            'name' => $profile['name'] ?: $user['name'],
        ));
    }

    /**
     * Get OAuth2 configured service
     *
     * @access public
     * @return \Core\OAuth2
     */
    public function getService()
    {
        if (empty($this->service)) {
            $this->service = $this->oauth->createService(
                GITHUB_CLIENT_ID,
                GITHUB_CLIENT_SECRET,
                $this->helper->url->to('oauth', 'github', array(), '', true),
                'https://github.com/login/oauth/authorize',
                'https://github.com/login/oauth/access_token',
                array()
            );
        }

        return $this->service;
    }

    /**
     * Get Github profile
     *
     * @access public
     * @param  string  $code
     * @return array
     */
    public function getProfile($code)
    {
        $this->getService()->getAccessToken($code);

        return $this->httpClient->getJson(
            'https://api.github.com/user',
            array($this->getService()->getAuthorizationHeader())
        );
    }
}
