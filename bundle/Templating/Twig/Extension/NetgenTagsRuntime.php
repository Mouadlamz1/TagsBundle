<?php

declare(strict_types=1);

namespace Netgen\TagsBundle\Templating\Twig\Extension;

use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\LanguageService;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use Netgen\TagsBundle\API\Repository\TagsService;
use Netgen\TagsBundle\API\Repository\Values\Tags\Tag;

final class NetgenTagsRuntime
{
    /**
     * @var \Netgen\TagsBundle\API\Repository\TagsService
     */
    private $tagsService;

    /**
     * @var \eZ\Publish\API\Repository\LanguageService
     */
    private $languageService;

    /**
     * @var \eZ\Publish\API\Repository\ContentTypeService
     */
    private $contentTypeService;

    public function __construct(
        TagsService $tagsService,
        LanguageService $languageService,
        ContentTypeService $contentTypeService
    ) {
        $this->tagsService = $tagsService;
        $this->languageService = $languageService;
        $this->contentTypeService = $contentTypeService;
    }

    /**
     * Returns tag keyword for provided tag ID.
     *
     * @param \Netgen\TagsBundle\API\Repository\Values\Tags\Tag|int|string $tagId
     *
     * @return string
     */
    public function getTagKeyword($tagId): string
    {
        if ($tagId instanceof Tag) {
            // BC with Tags Bundle v3
            return $tagId->getKeyword() ?? '';
        }

        try {
            $tag = $this->tagsService->loadTag((int) $tagId);
        } catch (NotFoundException $e) {
            return '';
        }

        return $tag->getKeyword() ?? '';
    }

    /**
     * Returns the language name for specified language code.
     */
    public function getLanguageName(string $languageCode): string
    {
        return $this->languageService->loadLanguage($languageCode)->name;
    }

    /**
     * Returns content type name for provided content type ID or content type object.
     *
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType|int $contentType
     *
     * @return string
     */
    public function getContentTypeName($contentType): string
    {
        if (!$contentType instanceof ContentType) {
            try {
                $contentType = $this->contentTypeService->loadContentType($contentType);
            } catch (NotFoundException $e) {
                return '';
            }
        }

        return $contentType->getName() ?? '';
    }
}
