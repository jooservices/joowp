<?php

declare(strict_types=1);

namespace Modules\Core\Services\WordPress\Contracts;

interface SdkContract
{
    /**
     * Retrieve a collection of posts.
     *
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function posts(array $query = []): array;

    /**
     * Retrieve a single post by its identifier.
     *
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function post(int $id, array $query = []): array;

    /**
     * Retrieve a collection of pages.
     *
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function pages(array $query = []): array;

    /**
     * Retrieve media assets.
     *
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function media(array $query = []): array;

    /**
     * Retrieve taxonomy categories.
     *
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function categories(array $query = []): array;

    /**
     * Retrieve a single category.
     *
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function category(int $id, array $query = []): array;

    /**
     * Create a category.
     *
     * @param  array<string, mixed>  $payload
     * @return array<int|string, mixed>
     */
    public function createCategory(array $payload): array;

    /**
     * Update a category.
     *
     * @param  array<string, mixed>  $payload
     * @return array<int|string, mixed>
     */
    public function updateCategory(int $id, array $payload): array;

    /**
     * Delete a category.
     *
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function deleteCategory(int $id, array $query = []): array;

    /**
     * Retrieve taxonomy tags.
     *
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function tags(array $query = []): array;

    /**
     * Retrieve a single tag.
     *
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function tag(int $id, array $query = []): array;

    /**
     * Create a tag.
     *
     * @param  array<string, mixed>  $payload
     * @return array<int|string, mixed>
     */
    public function createTag(array $payload): array;

    /**
     * Update a tag.
     *
     * @param  array<string, mixed>  $payload
     * @return array<int|string, mixed>
     */
    public function updateTag(int $id, array $payload): array;

    /**
     * Delete a tag.
     *
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function deleteTag(int $id, array $query = []): array;

    /**
     * Retrieve users.
     *
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function users(array $query = []): array;

    /**
     * Perform a full-text search.
     *
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function search(array $query = []): array;

    /**
     * Execute a raw GET request against the API.
     *
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function get(string $resource, array $query = []): array;

    /**
     * Exchange WordPress credentials for a JWT token.
     *
     * @return array<int|string, mixed>
     */
    public function token(string $username, string $password): array;
}
