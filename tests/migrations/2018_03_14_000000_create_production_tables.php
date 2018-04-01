<?php

use Illuminate\Database\Migrations\Migration;

require __DIR__.'/../../database/migrations/create_images_table.php.stub';
require __DIR__.'/../../database/migrations/create_image_attachments_table.php.stub';

class CreateProductionTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        (new CreateImagesTable())->up();
        (new CreateImageAttachmentsTable())->up();
    }
}
