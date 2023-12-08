<?php
class SceneModel{
    //
    static public function createScene($data){
        
    }

    static public function  executeQuery($query,$confirmCod = 0,$data=null,$fetch=false){
        $fields = array(
            "scen_id",
            "scen_number",
            "scen_duration",
            "scen_place",
            "scen_argument",
            "dayT_id",
            "spac_id");
        $statement= Connection::doConnection()->prepare($query);
        if(isset($data)){
            foreach(array_keys($fields) as $index){
                $pattern = '/^.*:'.$fields[$index].'.*$/';
                $result = (preg_match($pattern,$query));
                if(!$result) continue;
                switch($index){
                    case 0:
                        $statement->bindParam(":scen_id", $data["scen_id"],PDO::PARAM_INT);
                        break;
                    case 1:
                        $statement->bindParam(":scen_number", $data["scen_number"],PDO::PARAM_INT);
                        break;
                    case 2:
                        $statement->bindParam(":scen_duration", $data["scen_duration"],PDO::PARAM_INT);
                        break;
                    case 3:
                        $statement->bindParam(":scen_place", $data["scen_place"],PDO::PARAM_STR);
                        break;
                    case 4:
                        $statement->bindParam(":scen_argument", $data["scen_argument"],PDO::PARAM_STR);
                        break;
                    case 5:
                        $statement->bindParam(":dayT_id", $data["dayT_id"],PDO::PARAM_INT);
                        break;
                    case 6:
                        $statement->bindParam(":spac_id", $data["spac_id"],PDO::PARAM_INT);
                        break;
                }
            }
        }

        if(preg_match('/^SELECT.*$/',$query)){
            $error = $statement->execute() ? false : Connection::doConnection()->errorInfo();
            if($error != false) return array(910,$error->getMessage());
            if($fetch) return array($confirmCod,$statement->fetchAll());
            return array($confirmCod,$statement);
        }
        else{
            $error = $statement->execute() ? false : Connection::doConnection()->errorInfo();
            $statement-> closeCursor();
            $statement = null;
            if($error != false) return array(910,$error->getMessage());
            else return $confirmCod;
        }
    }
}
?>