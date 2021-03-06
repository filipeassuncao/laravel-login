<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;

class AuthenticationController extends Controller
{
    /**
     * @OA\Post(
     *   tags={"Acesso"},
     *   summary="Autenticar usuário",
     *   description="Retorna usuário logado",
     *   path="/app/login",
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="password", type="string"),
     *                 @OA\Examples(example="register", summary="Login",
     *          value={
     *               "email": "exemplo@gmail.com",
     *               "password": "123a123a",
     *         }),
     *       )
     *     ),
     *   @OA\Response(
     *      response=200,
     *      description="Login efetuado",
     *       @OA\JsonContent(ref="#/components/schemas/loginResponse")
     *    ),
     *    @OA\Response(
     *      response=401,
     *      description="Senha incorreta, favor revisar",
     *      @OA\JsonContent(
     *           @OA\Property(property="success", type="string"),
     *           @OA\Property(property="data", type="object",
     *               @OA\Property(property="message", type="string"),
     *           ),
     *           example={
     *                        "success": "false",
     *                           "data": {
     *                               "message": "Senha incorreta, favor revisar."
     *                           }
     *                     }
     *       ),
     *    ),
     *   @OA\Response(
     *      response=422,
     *      description="Campos inválidos",
     *      @OA\JsonContent(
     *           @OA\Property(property="success", type="string"),
     *           @OA\Property(property="data", type="object",
     *               @OA\Property(property="email", type="string"),
     *               @OA\Property(property="password", type="string"),
     *           ),
     *           @OA\Examples(example="login", summary="Campos obrigatórios",
     *                  value ={
     *                        "success" : "false",
     *                        "data": {
     *                               "email": {
     *                                   "O campo email é obrigatório."
     *                               },
     *                               "password": {
     *                                   "O campo senha é obrigatório."
     *                               }
     *                           }
     *                     }),
     *          @OA\Examples(example="login2", summary="Campos inválidos",
     *          value={
     *                        "success" : "false",
     *                        "data": {
     *                               "email": {
     *                                   "Email não cadastrado"
     *                               },
     *                               "password": {
     *                                   "O campo senha deve ter pelo menos 8 caracteres"
     *                               }
     *                           }
     *         }),
     *       ),
     *    )
     * )
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['success' => 'false','data' => ['message' => 'Senha incorreta, favor revisar.']], 401);
        }

        return $this->createNewToken($token);
    }

    /**
     * @OA\Post(
     *   tags={"Acesso"},
     *   summary="Desconectar usuário",
     *   description="Desconecta um usuário conectado",
     *   path="/app/logout",
     *   security={
     *           {"bearerAuth": {}}
     *       },
     *   @OA\Response(
     *      response=200,
     *      description="Usuário Desconectado",
     *      @OA\JsonContent(
     *         @OA\Property(property="success", type="string"),
     *         @OA\Property(property="data", type="object",
     *               @OA\Property(property="message", type="string"),
     *           ),
     *
     *           example= {
     *                        "success":    "true",
     *                        "data":  {
     *                              "message": "Usuário desconectado com sucesso"
     *                        }
     *                    }
     *       ),
     *    ),
     *    @OA\Response(response=403,ref="#/components/responses/forbidden"),
     *    @OA\Response(response=401,ref="#/components/responses/unauthorized")
     *
     * )
     * Loggout.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        return response()->json(
            ['success' => 'true',
            "data" => [
                'message' => 'Usuário desconectado com sucesso']
        ]);
    }
    /**
     * @OA\Post(
     *   tags={"Acesso"},
     *   summary="Obter novo token",
     *   description="Retorna informações do usuário e um novo token",
     *   path="/app/refresh",
     *    security={
     *           {"bearerAuth": {}}
     *       },
     *   @OA\Response(
     *      response=200,
     *      description="Token atualizado",
     *      @OA\JsonContent(ref="#/components/schemas/loginResponse")
     *    ),
     *    @OA\Response(response=403,ref="#/components/responses/forbidden"),
     *    @OA\Response(response=401,ref="#/components/responses/unauthorized")
     *    ),
     * )
     *
     * Refresh token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth::refresh());
    }
    /**
     * @OA\Get(
     *   tags={"Acesso"},
     *   summary="Obter dados do usuário conectado",
     *   description="Retorna informações do usuário conectado",
     *   path="/app/me",
     *    security={
     *           {"bearerAuth": {}}
     *       },
     *   @OA\Response(
     *      response=200,
     *      description="Dados do usuário conectado",
     *      @OA\JsonContent(
     *        @OA\Property(property="success", type="string"),
     *       @OA\Property(property="data", type="object",
     *        @OA\Property(ref="#/components/schemas/user")
     *        ),
     *      example={
     *          "success" : "true",
     *          "data" : {
     *                       "email": "teste@gmail.comffffffffff",
     *                       "uuid": "fbd5d732-137b-462b-bfd1-d32b23209fa5",
     *                       "updated_at": "2022-02-09T19:32:49.000000Z",
     *                       "created_at": "2022-02-09T19:32:49.000000Z",
     *                       "id": 470
     *                      }
     *      },
     *       ),
     *    ),
     *    @OA\Response(response=403,ref="#/components/responses/forbidden"),
     *    @OA\Response(response=401,ref="#/components/responses/unauthorized")
     *    ),
     * )
     *
     * Obtém usuário logado.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(["success" => "true", "data" =>auth()->user()]);
    }
    /**
     * Obtém estrutura do token.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        return response()->json(['success' => 'true',
            'data' => [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth::factory()->getTTL() * 60,
            'user' => auth()->user()
            ]
        ]);
    }
}
