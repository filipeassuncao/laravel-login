<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * @test
     */
    public function shouldRegisterUser()
    {
        $response = $this->post(
            '/app/users',
            [
                'email' => 'testuser@gmail.com',
                'email_confirmation' => 'testuser@gmail.com',
                'name' => 'Exemplo',
                'cpf' => '54528600021',
                'password' => '123456ff',
                'password_confirmation' => '123456ff',
                'terms_of_use' => 'true'
            ]
        );

        $response->assertJsonFragment(['email' => 'testuser@gmail.com']);

        $response->assertSuccessful();
    }

    /**
     * @test
     */
    public function shoudNotRegisterUserAndReturnValidateErrors()
    {
        $response = $this->post('/app/users');

        $response->assertJson(
            [
                'success' => false,
                'error' => [
                    'email' => [
                        'O campo email é obrigatório.'
                    ],
                    'name' => [
                        'O campo nome é obrigatório.'
                    ],
                    'cpf' => [
                        'O campo cpf é obrigatório.'
                    ],
                    'password' => [
                        'O campo senha é obrigatório.'
                    ],
                    'terms_of_use' => [
                        'O campo termos de uso é obrigatório.'
                    ]
                ]
            ]
        );

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function shoudNotRegisterUserAndReturnAllOthersErrors()
    {
        $response = $this->post(
            '/app/users',
            [
                'email' => 'hakuna.com', 'password' => '123', 'terms_of_use' => false
            ]
        );

        $response->assertJson(
            [
                'success' => false,
                'error' => [
                    'email' => [
                        'O campo email deve ser um endereço de e-mail válido.',
                        'O campo email de confirmação não confere.'
                    ],
                    'password' => [
                        'O campo senha de confirmação não confere.',
                        'O campo senha deve ter pelo menos 8 caracteres.',
                        'O campo senha deve conter pelo menos uma letra.'
                    ],
                    'terms_of_use' => [
                        'É obrigatório o aceite dos Termos de uso',
                    ]
                ]
            ]
        );

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function shoudReturnModelNotFound()
    {
        $response = $this->get(
            '/app/users/9999999999999'
        );

        $response->assertStatus(JsonResponse::HTTP_NOT_FOUND);
    }
}
