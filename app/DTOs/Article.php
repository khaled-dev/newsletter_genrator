<?php

namespace App\DTOs;


/**
 * @method string getExternalId()
 * @method string getTitle()
 * @method string|null getDescription()
 * @method string|null getContent()
 * @method string|null getUrl()
 * @method string|null getImageUrl()
 * @method string|null getAuthorName()
 * @method string getPublishedAt()
 * @method string getSource()
 */
final class Article extends ArticleParent
{
    public function __construct(
        protected string $external_id,
        protected string $title,
        protected ?string $description,
        protected ?string $content,
        protected ?string $url,
        protected ?string $image_url,
        protected ?string $author_name,
        protected string $published_at,
        protected string $source = 'unknown',
    )
    {
    }
}
