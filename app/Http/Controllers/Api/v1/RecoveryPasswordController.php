<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordRecoveryRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\PasswordTokenValidateRequest;
use App\Models\User;
use App\Repositories\PasswordResetRepository;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class RecoveryPasswordController extends Controller
{
    private $repository;

    public function __construct(PasswordResetRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @OA\Post(
     *   tags={"Recuperação de senha"},
     *   summary="Recuperar senha",
     *   description="Envia um código de recuperação ao email informado.",
     *   path="/app/forgot-password",
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="email", type="string"),
     *         @OA\Examples(example="register", summary="Recuperar senha",
     *          value={
     *               "email": "exemplo@gmail.com",
     *         }),
     *       )
     *     ),
     *   @OA\Response(
     *      response=200,
     *      description="Código de recuperação enviado ao email.",
     *           @OA\JsonContent(
     *           @OA\Property(property="success", type="string"),
     *           @OA\Property(property="data", type="object",
     *               @OA\Property(property="message", type="string"),
     *           ),
     *           example={
     *                        "success": "true",
     *                           "data": {
     *                               "message": "Código de recuperação de senha enviado ao seu email."
     *                           }
     *                     },
     *       ),
     *    ),
     * @OA\Response(
     *      response=422,
     *      description="Email inválido",
     *           @OA\JsonContent(
     *           @OA\Property(property="success", type="string"),
     *           @OA\Property(property="data", type="object",
     *               @OA\Property(property="message", type="string"),
     *           ),
     *           example={
     *                        "success": "false",
     *                           "data": {
     *                               "message": "E-mail não encontrado, favor revisar!"
     *                           }
     *                     },
     *       ),
     *    ),
     * )
     */
    public function sendPasswordResetToken(PasswordRecoveryRequest $request)
    {
        $user = User::where('email', $request->only('email'))->first();

        $this->repository->sendPasswordResentLink($user);
        return response()->json([
            "success" => "true",
            "data" => [
                "message" => "Código de recuperação de senha enviado ao seu email."
            ]
        ]);

    }

       /**
     * @OA\Post(
     *   tags={"Recuperação de senha"},
     *   summary="Validar código de verificação",
     *   description="Válida código de verificação enviado ao email.",
     *   path="/app/validate-password-token",
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="password_token", type="string"),
     *         @OA\Examples(example="password_token", summary="Recuperar senha",
     *          value={
     *               "password_token": "z5xYKu",
     *         }),
     *       )
     *     ),
     *   @OA\Response(
     *      response=200,
     *      description="Código de verificação válido,
                        retorna um novo código de verificação,
                        que será utilizado como parâmetro na rota new-password",
     *           @OA\JsonContent(
     *           @OA\Property(property="success", type="string"),
     *           @OA\Property(property="data", type="object",
     *               @OA\Property(property="message", type="string"),
     *           ),
     *           example={
     *                   "success": "true",
     *                   "data": {
     *                       "password_token": "L025fa"
     *                   }
     *                },
     *       ),
     *    ),
     *   @OA\Response(
     *      response=403,
     *      description="Código de verificação expirado",
     *           @OA\JsonContent(
     *           @OA\Property(property="success", type="string"),
     *           @OA\Property(property="data", type="object",
     *               @OA\Property(property="message", type="string"),
     *           ),
     *           example={
     *                   "success": "false",
     *                   "data": {
     *                       "message": "Código de verificação expirado."
     *                   }
     *                },
     *       ),
     *    ),
     * @OA\Response(
     *      response=422,
     *      description="Código de verificação inválido",
     *           @OA\JsonContent(
     *           @OA\Property(property="success", type="string"),
     *           @OA\Property(property="data", type="object",
     *               @OA\Property(property="message", type="string"),
     *           ),
     *     @OA\Examples(example="Código verificação inválido", summary="Código verificação inválido",
     *        value={
     *        "success": "false",
     *        "data": {
     *                  "message": "Código de verificação inválido."
     *             },
     *        }),
     *       @OA\Examples(example="Código de verificação maior que 6 caracteres", summary="Maior do que 6 caracteres",
     *          value={
     *          "success": "false",
     *          "data": {
     *                  "message": "O campo password token não pode ser superior a 6 caracteres."
     *             },
     *       })
     *       ),
     *    ),
     * )
     */
    public function validatePasswordResetToken(PasswordTokenValidateRequest $request)
    {
        $resetToken = $this->repository->getResetToken($request->get('password_token'));

        if(empty($resetToken)) {
            return response()->json([
                "success" => "false",
                "data" => [
                    "message" => "Código de verificação inválido."
                ]
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if(Carbon::now()->greaterThan($resetToken->expires_at)) {
            return response()->json([
                "success" => "false",
                "data" => [
                    "message" => "Código de verificação expirado."
                ]
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $newToken = $this->repository->getResetIdentifierCode($resetToken);

        if($newToken) {

            $this->repository->expiresTokenNow($resetToken);

            return response()->json([
                "success" => "true",
                "data" => [
                    "password_token" => $newToken
                ]
            ]);
        }
    }


    /**
     * @OA\Post(
     *   tags={"Recuperação de senha"},
     *   summary="Nova senha",
     *   description="Criação de nova senha.",
     *   path="/app/new-password",
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="password_token", type="string"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="password_confirmation", type="string"),
     *         @OA\Examples(example="password_token", summary="Nova senha",
     *          value={
     *               "password_token": "L025fa",
     *               "password" : "123f123f",
     *               "password_confirmation" : "123f123f"
     *         }),
     *       )
     *     ),
     *   @OA\Response(
     *      response=200,
     *      description="Senha alterada com sucesso.",
     *           @OA\JsonContent(
     *           @OA\Property(property="success", type="string"),
     *           @OA\Property(property="data", type="object",
     *               @OA\Property(property="message", type="string"),
     *           ),
     *           example={
     *                   "success": "true",
     *                   "data": {
     *                       "message": "Senha alterada com sucesso."
     *                   }
     *                },
     *       ),
     *    ),
     *   @OA\Response(
     *      response=403,
     *      description="Código de verificação expirado",
     *           @OA\JsonContent(
     *           @OA\Property(property="success", type="string"),
     *           @OA\Property(property="data", type="object",
     *               @OA\Property(property="message", type="string"),
     *           ),
     *           example={
     *                   "success": "false",
     *                   "data": {
     *                       "message": "Código de verificação expirado."
     *                   }
     *                },
     *       ),
     *    ),
     * @OA\Response(
     *      response=422,
     *      description="Código de verificação inválido",
     *           @OA\JsonContent(
     *           @OA\Property(property="success", type="string"),
     *           @OA\Property(property="data", type="object",
     *               @OA\Property(property="message", type="string"),
     *           ),
     *      @OA\Examples(example="Código de verificação inválido", summary="Código de verificação inválido",
     *          value={
     *          "success": "false",
     *          "data": {
     *                  "message": "Código de verificação inválido."
     *             },
     *       }),
     *       @OA\Examples(example="Código de verificação maior que 6 caracteres", summary="Maior do que 6 caracteres",
     *          value={
     *          "success": "false",
     *          "data": {
     *                  "message": "O campo password token não pode ser superior a 6 caracteres."
     *             },
     *          })
     *       ),
     *     ),
     *   ),
     * )
     */
    public function setNewAccountPassword(PasswordResetRequest $request)
    {
        $verifyToken = $this->repository->getResetToken($request->get('password_token'));

        if(empty($verifyToken)) {
            return response()->json([
                "success" => "false",
                "data" => [
                    "message" => "Token para alteração de senha inválido."
                ]
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if(Carbon::now()->greaterThan($verifyToken->expires_at)) {
            return response()->json([
                "success" => "false",
                "data" => [
                    "message" => "Token para alteração de senha expirado."
                ]
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $newPassword = Hash::make($request->all()['password']);
        $verifyToken->user->password = $newPassword;

        if($verifyToken->user->save()) {

            $this->repository->expiresTokenNow($verifyToken);

            return response()->json([
                "success" => "true",
                "data" => [
                    "message" => "Senha alterada com sucesso."
                ]
            ]);
        }
    }
}
