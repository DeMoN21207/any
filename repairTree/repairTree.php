<?php


class RepairTree
{
    /** Список таблиц для перестройки */
    private $tables = [];

    /** @param array $tables */
    public function setTables(array $tables): void
    {
        $this->tables = $tables;
    }


    /**
     * Перестроение дерева
     * @return void
     */
    public function repairTree(): void
    {

        foreach ($this->tables as $name_table) {
            $this->createTempTables($name_table);
            $startId = 0;

            $sql = 'SELECT * FROM tmp_tree11  WHERE parent IS NULL AND key_left IS NULL AND key_right IS NULL LIMIT 1';
            while ($this->db->query($sql)->row_array()) {
                $startId;
                $left_id = $startId + 1;
                $update_root_sql = "UPDATE tmp_tree11 SET key_left ={$startId} , key_right = {$left_id}
                                WHERE id IN(SELECT id FROM tmp_tree11
                                WHERE parent IS NULL AND key_left IS NULL AND key_right IS NULL LIMIT 1);";
                $this->db->query($update_root_sql);
                $startId += 2;
            }
            while ($this->db->query('SELECT * FROM tmp_tree11 WHERE key_left IS NULL LIMIT 1')->row_array()) {

                $currentId = $this->db
                    ->query(' SELECT tmp_tree11.id FROM tmp_tree11
	                                            INNER JOIN tmp_tree11 AS parents ON tmp_tree11.parent = parents.id
	                                            WHERE tmp_tree11.key_left IS NULL AND parents.key_left
	                                            IS NOT NULL ORDER BY tmp_tree11.parent LIMIT 1;')
                    ->row_array()['id'];

                $currentParentId = $this->db
                    ->query("SELECT parent FROM tmp_tree11 WHERE id = {$currentId};")
                    ->row_array()['parent'];

                $currentLeft = $this->db
                    ->query("SELECT key_left FROM tmp_tree11 WHERE id ={$currentParentId};")
                    ->row_array()['key_left'];

                if (!is_null($currentLeft)) {
                    $this->db->query("UPDATE tmp_tree11 SET key_right = key_right + 2 WHERE key_right > {$currentLeft};");
                    $this->db->query("UPDATE tmp_tree11 SET key_left = key_left + 2 WHERE key_left > {$currentLeft};");
                    $this->db->query("UPDATE tmp_tree11 SET key_left = {$currentLeft} + 1,key_right = {$currentLeft} + 2 WHERE id = {$currentId};");
                }
            }
            $this->updateData($name_table);
            $this->cleanTempTables();
        }
        echo 'Trees success repair';
        exit;
    }

    /**
     * Создание темповой таблицы (записываем родителей)
     * @param $table_name
     * @return void
     */
    private function createTempTables($table_name): void
    {
        $create_table = "SELECT A.id,A.key_left,A.key_right,IF(B.ID IS NULL,1,B.ID) AS PARENT
		INTO tmp_tree11
        FROM {$table_name} AS A
        LEFT OUTER JOIN {$table_name}  AS B ON B.key_left =
	                        (SELECT MAX(C.key_left) FROM {$table_name}  AS C
		                    WHERE A.key_left > C.key_left AND A.key_right < C.key_right)
        ORDER BY parent";
        $this->db->query($create_table);
        $this->dataConversion();
    }

    /**
     * Преобразование данных
     * @return void
     */
    private function dataConversion(): void
    {
        $delete_duplicates = "DELETE FROM tmp_tree11 a
                            WHERE a.ctid <> (SELECT min(b.ctid) FROM   tmp_tree11 b WHERE  a.id = b.id);";
        $set_null_parent = "UPDATE tmp_tree11 SET parent = NULL WHERE id = 1;";
        $set_null_all_records = "UPDATE tmp_tree11 SET key_left = NULL, key_right = NULL";

        $this->db->query($delete_duplicates);
        $this->db->query($set_null_parent);
        $this->db->query($set_null_all_records);
    }

    /**
     * Обновление данных
     * @param $table_name
     * @return void
     */
    private function updateData($table_name): void
    {
        $update_sql = "UPDATE {$table_name} as l
                        SET key_left = tt.key_left,key_right = tt.key_right
                        FROM tmp_tree11 AS tt
                        WHERE l.id = tt.id;";
        $update_keys = "UPDATE {$table_name} SET key_left = key_left+1,key_right=key_right+1;";
        $this->db->query($update_sql);
        $this->db->query($update_keys);
    }

    /**
     * Очистка темповой таблицы
     * @return void
     */
    private function cleanTempTables(): void
    {
        $drop_table = "DROP TABLE tmp_tree11;";
        $this->db->query($drop_table);
    }


}




