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

    public function index(FeedIndexRequest $request): JsonResponse
    {
        $feed = $this->feedContainer->feed(
            $request->cursor(),
            $request->userId(),
        );

        return $this->responseMaker->makeWithMeta(
            data: $feed['data'],
            meta: $feed['meta'],
        );
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->feedContainer->create(
            $request->payload(),
            $request->userId(),
        );

        return $this->responseMaker->make(
            data: $post,
            httpCode: HttpCodeConstant::CREATED,
        );
    }

    public function toggleLike(ApplicationRequest $request, int $postId): JsonResponse
    {
        $post = $this->feedContainer->toggleLike(
            $postId,
            $request->userId(),
        );

        return $this->responseMaker->make(data: $post);
    }

    public function storeComment(StoreCommentRequest $request, int $postId): JsonResponse
    {
        $post = $this->feedContainer->comment(
            $request->body(),
            $postId,
            $request->userId(),
        );

        return $this->responseMaker->make(
            data: $post,
            httpCode: HttpCodeConstant::CREATED,
        );
    }
}
