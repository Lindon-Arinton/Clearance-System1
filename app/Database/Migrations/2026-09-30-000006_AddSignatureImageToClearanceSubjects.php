<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The teacher's e-signature image is stamped onto each approval, so replacing
 * a signature later never changes clearances that were already signed.
 */
class AddSignatureImageToClearanceSubjects extends Migration
{
    public function up()
    {
        $this->forge->addColumn('clearance_subjects', [
            'signature_image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'signature_hash'],
        ]);

        // Backfill approvals signed by teachers who already had an uploaded signature.
        $this->db->query("UPDATE clearance_subjects cs JOIN teachers t ON t.id = cs.teacher_id
            SET cs.signature_image = t.signature_path
            WHERE cs.signed_at IS NOT NULL AND cs.signature_image IS NULL AND t.signature_path IS NOT NULL");
    }

    public function down()
    {
        $this->forge->dropColumn('clearance_subjects', 'signature_image');
    }
}
