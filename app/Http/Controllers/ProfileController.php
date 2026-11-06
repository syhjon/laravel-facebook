<?php

namespace App\Http\Controllers;

use App\Constants\ProfileConstant;
use App\Contracts\Containers\ProfileContainerInterface;
use App\Contracts\Responses\ResponseMakerInterface;
use App\Http\Requests\ApplicationRequest;
use App\Http\Requests\Profile\UpdateProfileImageRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileContainerInterface $profileContainer,
        ResponseMakerInterface $responseMaker,
    ) {
        parent::__construct($responseMaker);
    }

    public function page(ApplicationRequest $applicationRequest): View
    {
        return view('app', [
            'applicationData' => $this->profileContainer->page($applicationRequest->userId()),
        ]);
    }

    public function show(ApplicationRequest $applicationRequest): JsonResponse
    {
        return $this->responseMaker->createResponse(
            responseData: $this->profileContainer->profile($applicationRequest->userId()),
        );
    }

    public function update(UpdateProfileRequest $updateProfileRequest): JsonResponse
    {
        $updatedProfile = $this->profileContainer->updateProfile(
            $updateProfileRequest->userId(),
            $updateProfileRequest->payload(),
        );

        return $this->responseMaker->createResponse(
            responseData: $updatedProfile,
            message: ProfileConstant::PROFILE_UPDATED_MESSAGE,
        );
    }

    public function updateImage(UpdateProfileImageRequest $updateProfileImageRequest): JsonResponse
    {
        $updatedProfile = $this->profileContainer->updateProfileImage(
            $updateProfileImageRequest->userId(),
            $updateProfileImageRequest->profileImage(),
        );

        return $this->responseMaker->createResponse(
            responseData: $updatedProfile,
            message: ProfileConstant::PROFILE_IMAGE_UPDATED_MESSAGE,
        );
    }
}
