<?php

namespace App\Exceptions;

use App\Traits\RespondsWithHttpStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Throwable;

class Handler extends ExceptionHandler
{
    use RespondsWithHttpStatus;
    
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Determine if the exception should be reported.
     */
    public function shouldReport(Throwable $e): bool
    {
        // Don't report SMTP timeout errors
        if ($e instanceof UnexpectedResponseException) {
            $message = $e->getMessage();
            if (str_contains($message, '421') && str_contains($message, 'Timeout - closing connection')) {
                return false;
            }
        }

        return parent::shouldReport($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($request->is('api/*') || $request->wantsJson()) {
            if ($exception instanceof ModelNotFoundException) {
                $model = str(class_basename($exception->getModel()))->headline()->lower();
                return $this->failure("The requested resource {$model} information was not found", 404);
            }

            if ($exception instanceof NotFoundHttpException) {
                return $this->failure('Your app version is outdated. Please upgrade to the latest version.', 404);
            }

            if ($exception instanceof AuthorizationException) {
                return $this->failure($exception->getMessage(), 403);
            }

            if ($exception instanceof AccessDeniedHttpException) {
                return $this->failure('This action is unauthorized.', 403);
            }

            if ($exception instanceof ThrottleRequestsException) {
                return $this->failure('Too many attempts was made please try later.', 429);
            }

            if ($exception instanceof MethodNotAllowedHttpException) {
                return $this->failure($exception->getMessage(), 405);
            }

            if ($exception instanceof HttpException) {
                return $this->failure($exception->getMessage(), $exception->getStatusCode());
            }

            if ($exception instanceof QueryException) {
                return $this->failure("Whoops there were some problems on our end. Our team has been notified, and we're working to fix it", 500);
            }

            if ($exception instanceof RelationNotFoundException) {
                return $this->failure("Whoops there were some problems on our end. Our team has been notified, and we're working to fix it", 500);
            }

            if ($exception instanceof AuthenticationException) {
                return $this->failure('You are not authenticated please login.', 401);
            }

            if ($exception instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => $this->summarize($exception->validator),
                    'errors' => $exception->errors()
                ], 422);
            }

            // catch all exception
            return $this->failure("An unexpected error occurred. Our team has been notified, and we're working to fix it.", 500);
        }

        return parent::render($request, $exception);
    }

    /**
     * Create an error message summary from the validation errors.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator#
     * @return string
     */
    protected static function summarize($validator)
    {
        $messages = $validator->errors()->all();

        if (! count($messages) || ! is_string($messages[0])) {
            return $validator->getTranslator()->get('The given data was invalid.');
        }

        $message = array_shift($messages);

        return "Whoops something went wrong, ". $message;
    }
}
