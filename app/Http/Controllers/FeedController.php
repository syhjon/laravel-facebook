<?php

namespace App\Http\Controllers;

use App\Constants\HttpCodeConstant;
use App\Contracts\Containers\FeedContainerInterface;
use App\Contracts\Responses\ResponseMakerInterface;
use App\Http\Requests\ApplicationRequest;
use App\Http\Requests\Feed\FeedIndexRequest;
use App\Http\Requests\Feed\StoreCommentRequest;
use App\Http\Requests\Feed\StorePostRequest;
use Illuminate\Http\JsonResponse;

class FeedController extends Controller
{
    public function __construct(
        private readonly FeedContainerInterface $feedContainer,
        ResponseMakerInterface $responseMaker,
    ) {
        parent::__construct($responseMaker);
    }

    public function index(FeedIndexRequest $feedIndexRequest): JsonResponse
    {
        $feedResult = $this->feedContainer->feed(
            $feedIndexRequest->cursor(),
            $feedIndexRequest->userId(),
        );

        return $this->responseMaker->createResponseWithMetadata(
            responseData: $feedResult['data'],
            metadata: $feedResult['meta'],
        );
    }

    public function store(StorePostRequest $storePostRequest): JsonResponse
    {
        $createdPost = $this->feedContainer->create(
            $storePostRequest->payload(),
            $storePostRequest->userId(),
        );

        return $this->responseMaker->createResponse(
            responseData: $createdPost,
            httpCode: HttpCodeConstant::CREATED,
        );
    }

    public function toggleLike(ApplicationRequest $applicationRequest, int $postId): JsonResponse
    {
        $updatedPost = $this->feedContainer->toggleLike(
            $postId,
            $applicationRequest->userId(),
        );

        return $this->responseMaker->createResponse(responseData: $updatedPost);
    }

    public function storeComment(StoreCommentRequest $storeCommentRequest, int $postId): JsonResponse
    {
        $updatedPost = $this->feedContainer->comment(
            $storeCommentRequest->body(),
            $postId,
            $storeCommentRequest->userId(),
        );

        return $this->responseMaker->createResponse(
            responseData: $updatedPost,
            httpCode: HttpCodeConstant::CREATED,
        );
    }
}
