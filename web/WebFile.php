<?php

namespace asb\yii2\common_2_170212\web;

/**
 * Class provide synchronization with uploaded file and its mirror in web root.
 * Upload files area placed not in web root.
 *
 * todo: processing filenames in formats
 * 'filename.NNNxNNN.jpg', 'filename.NNNxNNNcut.jpg', 'filename.wNNN.jpg', 'filename.hNNN.jpg'
 * for scaling single source file 'filename.jpg'
 *
 * @author ASB <ab2014box@gmail.com>
 */
class WebFile extends BaseWebFile
{
}
