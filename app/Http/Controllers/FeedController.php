<?php

namespace App\Http\Controllers;

use App\Contracts\Containers\FeedContainerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedController extends Controller
{
    public function __construct(
        private readonly FeedContainerInterface $feedContainer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $cursor = $request->string('cursor')->toString() ?: null;

        return response()->json(
            $this->feedContainer->feed($cursor, $request->user()->getKey()),
        );
    }

    public function store(Request $request): JsonResponse
    {
        $post = DB::transaction(
            fn (): array => $this->feedContainer->create(
                $request->all(),
                $request->user()->getKey(),
            ),
        );

        return response()->json(['data' => $post], 201);
    }

    public function toggleLike(Request $request, int $postId): JsonResponse
    {
        $post = DB::transaction(
            fn (): array => $this->feedContainer->toggleLike(
                $postId,
                $request->user()->getKey(),
            ),
        );

        return response()->json(['data' => $post]);
    }

    public function storeComment(Request $request, int $postId): JsonResponse
    {
        $post = DB::transaction(
            fn (): array => $this->feedContainer->comment(
                $request->all(),
                $postId,
                $request->user()->getKey(),
            ),
        );

        return response()->json(['data' => $post], 201);
    }
}
