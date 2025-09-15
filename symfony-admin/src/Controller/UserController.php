<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\FilterDto;
use App\Dto\UserRequestDto;
use App\Enum\UserMessage;
use App\Form\UserFilterType;
use App\Form\UserFormType;
use App\Service\AuthenticationServiceInterface;
use App\Service\UserServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users', name: 'admin_users_')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly AuthenticationServiceInterface $authenticationService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $token = $this->authenticationService->getTokenOrRedirect();
        if ($token instanceof Response) {
            return $token;
        }

        $filterForm = $this->createForm(UserFilterType::class);
        $filterForm->handleRequest($request);

        $filterDto = FilterDto::fromRequest($request);

        if ($filterForm->isSubmitted() && ! $filterForm->isValid()) {
            foreach ($filterForm->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        $result = $this->userService->getUsers($token, $filterDto);

        foreach ($result->getErrors() as $error) {
            $this->addFlash('error', $error);
        }

        return $this->render('admin/users/index.html.twig', [
            'users' => $result->users,
            'current_filters' => $filterDto->getCurrentFilters(),
            'sort_by' => $filterDto->sortBy,
            'sort_order' => $filterDto->sortOrder,
            'filter_form' => $filterForm->createView(),
            'api_available' => $result->isApiAvailable(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $token = $this->authenticationService->getTokenOrRedirect();
        if ($token instanceof Response) {
            return $token;
        }

        $result = $this->userService->getUser($token, $id);

        foreach ($result->getErrors() as $error) {
            $this->addFlash('error', $error);
        }

        if (! $result->isSuccess() || ! $result->user) {
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/users/show.html.twig', [
            'user' => $result->user,
            'api_available' => $result->isApiAvailable(),
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $token = $this->authenticationService->getTokenOrRedirect();
        if ($token instanceof Response) {
            return $token;
        }

        $form = $this->createForm(UserFormType::class, null, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userData = $form->getData();
            $userRequestDto = UserRequestDto::fromArray($userData);
            $result = $this->userService->createUser($token, $userRequestDto);

            foreach ($result->getErrors() as $error) {
                $this->addFlash('error', $error);
            }

            if ($result->isSuccess()) {
                $this->addFlash('success', UserMessage::USER_CREATED->value);
                return $this->redirectToRoute('admin_users_show', ['id' => $result->user->id]);
            }
        }

        return $this->render('admin/users/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): Response
    {
        $token = $this->authenticationService->getTokenOrRedirect();
        if ($token instanceof Response) {
            return $token;
        }

        $result = $this->userService->getUser($token, $id);

        foreach ($result->getErrors() as $error) {
            $this->addFlash('error', $error);
        }

        if (! $result->isSuccess() || ! $result->user) {
            return $this->redirectToRoute('admin_users_index');
        }

        $userData = $result->user->toFormArray();

        $form = $this->createForm(UserFormType::class, $userData, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userData = $form->getData();
            $userRequestDto = UserRequestDto::fromArray($userData);
            $updateResult = $this->userService->updateUser($token, $id, $userRequestDto);

            foreach ($updateResult->getErrors() as $error) {
                $this->addFlash('error', $error);
            }

            if ($updateResult->isSuccess()) {
                $this->addFlash('success', UserMessage::USER_UPDATED->value);
                return $this->redirectToRoute('admin_users_show', ['id' => $id]);
            }
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $result->user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id): Response
    {
        $token = $this->authenticationService->getTokenOrRedirect();
        if ($token instanceof Response) {
            return $token;
        }

        $result = $this->userService->deleteUser($token, $id);

        foreach ($result->getErrors() as $error) {
            $this->addFlash('error', $error);
        }

        if ($result->isSuccess()) {
            $this->addFlash('success', UserMessage::USER_DELETED->value);
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/import', name: 'import', methods: ['POST'])]
    public function import(): Response
    {
        $token = $this->authenticationService->getTokenOrRedirect();
        if ($token instanceof Response) {
            return $token;
        }

        $result = $this->userService->importUsers($token);

        foreach ($result->getErrors() as $error) {
            $this->addFlash('error', $error);
        }

        if ($result->isSuccess()) {
            $this->addFlash(
                'success',
                sprintf(UserMessage::USERS_IMPORTED->value, 100)
            );
        }

        return $this->redirectToRoute('admin_users_index');
    }
}
