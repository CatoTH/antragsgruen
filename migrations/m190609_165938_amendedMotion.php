<?php

use yii\db\Migration;

/**
 * Handles the creation of table `amendedMotion`.
 * Has foreign keys to the tables:
 *
 * - `motion`
 * - `amendment`
 */
class m190609_165938_create_junction_table_for_motion_and_amendment_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('amendedMotion', [
            'motionId' => $this->integer(),
            'amendmentId' => $this->integer(),
            'PRIMARY KEY(motionId, amendmentId)',
        ]);

        // creates index for column `motionId`
        $this->createIndex(
            'idx-amendedMotion-motionId',
            'amendedMotion',
            'motionId'
        );

        // add foreign key for table `motion`
        $this->addForeignKey(
            'fk-amendedMotion-motionId',
            'amendedMotion',
            'motionId',
            'motion',
            'id',
            'CASCADE'
        );

        // creates index for column `amendmentId`
        $this->createIndex(
            'idx-amendedMotion-amendmentId',
            'amendedMotion',
            'amendmentId'
        );

        // add foreign key for table `amendment`
        $this->addForeignKey(
            'fk-amendedMotion-amendmentId',
            'amendedMotion',
            'amendmentId',
            'amendment',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // drops foreign key for table `motion`
        $this->dropForeignKey(
            'fk-amendedMotion-motionId',
            'amendedMotion'
        );

        // drops index for column `motionId`
        $this->dropIndex(
            'idx-amendedMotion-motionId',
            'amendedMotion'
        );

        // drops foreign key for table `amendment`
        $this->dropForeignKey(
            'fk-amendedMotion-amendmentId',
            'amendedMotion'
        );

        // drops index for column `amendmentId`
        $this->dropIndex(
            'idx-amendedMotion-amendmentId',
            'amendedMotion'
        );

        $this->dropTable('amendedMotion');
    }
}
