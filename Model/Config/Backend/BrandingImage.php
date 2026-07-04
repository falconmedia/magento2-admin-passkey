<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Config\Backend;

use FalconMedia\AdminPasskey\Model\Branding\SvgSanitizer;
use Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface;
use Magento\Config\Model\Config\Backend\Image;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\MediaStorage\Model\File\UploaderFactory;

/**
 * Backend model for white-label branding image fields.
 *
 * Restricts uploads to a small set of safe raster/vector formats and, for SVG
 * files, rejects any markup that could execute script or pull in external
 * content before the native uploader stores the file under media/adminpasskey.
 */
class BrandingImage extends Image
{
    /**
     * Allowed upload extensions for branding assets.
     *
     * @var string[]
     */
    private const ALLOWED_EXTENSIONS = ['svg', 'png', 'jpg', 'jpeg', 'webp'];

    /**
     * @param array<string, mixed> $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        UploaderFactory $uploaderFactory,
        RequestDataInterface $requestData,
        Filesystem $filesystem,
        private readonly SvgSanitizer $svgSanitizer,
        private readonly FileDriver $fileDriver,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $uploaderFactory,
            $requestData,
            $filesystem,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Validate the SVG payload (when applicable) before delegating to the native uploader.
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function beforeSave()
    {
        $file = $this->getFileData();
        if (!empty($file) && isset($file['tmp_name'], $file['name'])) {
            $name = strtolower((string) $file['name']);
            $tmpName = (string) $file['tmp_name'];
            if ($tmpName !== '' && str_ends_with($name, '.svg')) {
                $this->svgSanitizer->assertSafe($this->fileDriver->fileGetContents($tmpName));
            }
        }

        return parent::beforeSave();
    }

    /**
     * Getter for allowed extensions of uploaded branding assets.
     *
     * @return string[]
     */
    protected function _getAllowedExtensions()
    {
        return self::ALLOWED_EXTENSIONS;
    }
}
