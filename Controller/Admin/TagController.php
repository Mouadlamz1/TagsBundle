<?php

namespace Netgen\TagsBundle\Controller\Admin;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\ContentTypeService;
use Netgen\TagsBundle\API\Repository\TagsService;
use Netgen\TagsBundle\API\Repository\Values\Tags\Tag;
use Netgen\TagsBundle\Form\Type\Admin\TagCreateType;
use Symfony\Component\HttpFoundation\Request;
use Netgen\TagsBundle\Form\Type\Admin\TagUpdateType;

class TagController extends Controller
{
    /**
     * @var \Netgen\TagsBundle\API\Repository\TagsService
     */
    protected $tagsService;

    /**
     * @var \eZ\Publish\API\Repository\ContentTypeService
     */
    protected $contentTypeService;

    /**
     * TagController constructor.
     *
     * @param \Netgen\TagsBundle\API\Repository\TagsService $tagsService
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     */
    public function __construct(TagsService $tagsService, ContentTypeService $contentTypeService)
    {
        $this->tagsService = $tagsService;
        $this->contentTypeService = $contentTypeService;
    }

    /**
     * @param $tagId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showTagAction($tagId)
    {
        $tag = $this->tagsService->loadTag($tagId);
        $relatedContent = $this->tagsService->getRelatedContent($tag, 0, 10);
        $synonyms = $this->tagsService->loadTagSynonyms($tag, 0, 10);
        $childrenTags = $this->tagsService->loadTagChildren($tag, 0, 10);
        $subTreeLimitations = $this->getSubtreeLimitations($tag);

        return $this->render('@NetgenTags/admin/tag/show.html.twig', array(
            'tag' => $tag,
            'relatedContent' => $relatedContent,
            'synonyms' => $synonyms,
            'childrenTags' => $childrenTags,
            'subTreeLimitations' => $subTreeLimitations,
        ));
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param $parentId
     * @param $languageCode
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addTagAction(Request $request, $parentId, $languageCode)
    {
        $tagCreateStruct = $this->tagsService->newTagCreateStruct($parentId, $languageCode);
        $tagCreateStruct->parentTagId = $parentId;
        $tagCreateStruct->mainLanguageCode = $languageCode;

        $form = $this->createForm(TagCreateType::class, $tagCreateStruct, array(
            'languageCode' => $languageCode,
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newTag = $this->tagsService->createTag($form->getData());

            return $this->redirectToRoute('netgen_tags_admin_tag_show', array(
                'tagId' => $newTag->id,
            ));
        }

        return $this->render('@NetgenTags/admin/tag/add.html.twig', array(
                'form' => $form->createView(),
        ));
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param $tagId
     * @param $languageCode
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateTagAction(Request $request, $tagId, $languageCode)
    {
        $tag = $this->tagsService->loadTag($tagId);

        $tagUpdateStruct = $this->tagsService->newTagUpdateStruct();
        $tagUpdateStruct->remoteId = $tag->remoteId;
        $tagUpdateStruct->alwaysAvailable = $tag->alwaysAvailable;

        foreach ($tag->keywords as $keywordLanguageCode => $keyword) {
            $tagUpdateStruct->setKeyword($keyword, $keywordLanguageCode);
        }

        $form = $this->createForm(TagUpdateType::class, $tagUpdateStruct, array(
            'languageCode' => $languageCode,
            'tag' => $tag,
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $updatedTag = $this->tagsService->updateTag($tag, $form->getData());

            return $this->redirectToRoute('netgen_tags_admin_tag_show', array(
                'tagId' => $updatedTag->id,
            ));
        }

        return $this->render('@NetgenTags/admin/tag/update.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Returns an array with subtree limitations for given tag.
     *
     * @param \Netgen\TagsBundle\API\Repository\Values\Tags\Tag $tag
     * @return array
     */
    protected function getSubtreeLimitations(Tag $tag)
    {
        $contentTypeGroups = $this->contentTypeService->loadContentTypeGroups();

        $result = array();

        foreach ($contentTypeGroups as $contentTypeGroup) {
            $contentTypes = $this->contentTypeService->loadContentTypes($contentTypeGroup);

            foreach ($contentTypes as $contentType) {
                $fieldDefinitions = $contentType->getFieldDefinitions();

                foreach ($fieldDefinitions as $fieldDefinition) {
                    if ($fieldDefinition->fieldTypeIdentifier === 'eztags') {
                        if ($fieldDefinition->getFieldSettings()['subTreeLimit'] === $tag->id) {
                            $limitation = array();
                            $limitation['contentTypeId'] = $contentType->id;
                            $limitation['attributeIdentifier'] = $fieldDefinition->identifier;
                            array_push($result, $limitation);
                        }
                    }
                }
            }
        }

        return $result;
    }
}
