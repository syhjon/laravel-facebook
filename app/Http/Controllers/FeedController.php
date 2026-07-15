<?php

namespace App\Http\Controllers;

use App\Constants\HttpCodeConstant;
use App\Contracts\Containers\FeedContainerInterface;
use App\Contracts\Responses\ResponseMakerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function __construct(
        private readonly FeedContainerInterface $feedContainer,
        ResponseMakerInterface $responseMaker,
    ) {
        parent::__construct($responseMaker);
    }

    public function index(Request $request): JsonResponse
    {
        $cursor = $request->string('cursor')->toString() ?: null;

        $feed = $this->feedContainer->feed(
            $cursor,
            $request->user()->getKey(),
        );

        return $this->responseMaker->makeWithMeta(
            data: $feed['data'],
            meta: $feed['meta'],
        );
    }

    public function store(Request $request): JsonResponse
    {
        $post = $this->feedContainer->create(
            $request->all(),
            $request->user()->getKey(),
        );

        return $this->responseMaker->make(
            data: $post,
            httpCode: HttpCodeConstant::CREATED,
        );
    }

    public function toggleLike(Request $request, int $postId): JsonResponse
    {
        $post = $this->feedContainer->toggleLike(
            $postId,
            $request->user()->getKey(),
        );

        return $this->responseMaker->make(data: $post);
    }

    public function storeComment(Request $request, int $postId): JsonResponse
    {
        $post = $this->feedContainer->comment(
            $request->all(),
            $postId,
            $request->user()->getKey(),
        );

        return $this->responseMaker->make(
            data: $post,
            httpCode: HttpCodeConstant::CREATED,
        );
    }
}
