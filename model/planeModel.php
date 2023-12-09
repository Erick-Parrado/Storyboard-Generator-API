<?php

require_once 'model/sceneModel.php';
require_once 'model/shotModel.php';
require_once 'model/framingModel.php';
require_once 'model/moveModel.php';

class PlaneModel{

    //GET
    static public function readPlane($plan_number,$scen_number,$proj_id){
        $data['plan_number'] = $plan_number;
        $data['scen_number'] = $scen_number;
        $data['proj_id'] = $proj_id;
        $data['plan_id']= self::exist($data);
        $query = 'SELECT * FROM scenes WHERE plan_id =:plan_id';
        return self::executeQuery($query,501,$data);
    }
    
    static public function readScenePlanes($scen_number = null,$proj_id = null){
        if($proj_id == null) throw new Exception(428);
        if($scen_number == null) throw new Exception(528);
        $data['proj_id'] = $proj_id;
        $data['scen_number'] = $scen_number;
        $data['scen_id'] = SceneModel::exist($data);
        if($data['scen_id'] == 0) throw new Exception(419);
        $query = 'SELECT * FROM planes WHERE scen_id =:scen_id ORDER BY plan_number ASC';
        return self::executeQuery($query,502,$data);
    }

    //POST
    static public function createPlane($proj_id,$scen_number,$data){
        $data['proj_id'] = $proj_id;
        $data['scen_number'] = $scen_number;
        $data['scen_id'] = SceneModel::exist($data);
        if($data['scen_id'] == 0) throw new Exception(419);
        //echo json_encode($data,JSON_UNESCAPED_UNICODE);
        if(!self::validNumberPlane($data)) throw new Exception(521);
        self::newNumberPlane($data); 
        $query = 'INSERT INTO planes(plan_duration, plan_description, plan_image, shot_id, fram_id, move_id, scen_id, plan_number) VALUES  (:plan_duration,:plan_description,:plan_image,:shot_id,:fram_id,:move_id,:scen_id,:plan_number)';
        return self::executeQuery($query,500,$data);
    }

    //PUT
    static public function updatePlane($scen_number,$proj_id,$data){
        SpaceModel::exist($data);
        DayTimeModel::exist($data);
        $data['proj_id']=$proj_id;
        $data['scen_number'] = $scen_number;
        $data['scen_id']= self::exist($data);
        if($data['scen_id']==0) throw new Exception(419);
        if(array_key_exists('scen_number',$data)){
            if($scen_number != $data['scen_number']){
                if(!self::validNumberPlane($data)) throw new Exception(421);
                self::updateNumberPlane($data,$scen_number);
            }
        } 
        return self::updateMethod($data);
    }

    //DELETE
    static public function deletePlane($scen_number,$proj_id){
        $data['proj_id']=$proj_id;
        $data['scen_number'] = $scen_number;
        $data['scen_id']= self::exist($data);
        if($data['scen_id']==0) throw new Exception(419);
        self::deleteNumberPlane($data);
        $query = 'DELETE FROM scenes WHERE scen_id = :scen_id';
        return self::executeQuery($query,404,$data);
    }
    

    //Extras
    static public function exist($data){
        //echo json_encode($data,JSON_UNESCAPED_SLASHES);
        $query = '';
        if(array_key_exists('plan_id',$data)){
            $query = "SELECT plan_id FROM scenes WHERE plan_id = :plan_id";
        }
        else{
            $query = "SELECT plan_id FROM planes WHERE plan_number=:plan_number AND scen_id = :scen_id";
        }
        $plan_id = self::executeQuery($query,1,$data,true);
        $plan_id = (isset($plan_id[1][0]['plan_id']))?$plan_id[1][0]['plan_id']:0;
        return ($plan_id>0)?$plan_id:0;
    }

    static private function validNumberPlane($data){
        //var_dump(self::readScenePlanes($data['scen_number'],$data['proj_id']));
        $scenCount = (self::readScenePlanes($data['scen_number'],$data['proj_id'])[1]->rowCount());
        return ($data['plan_number']<=$scenCount+1 && $data['plan_number']>0)?1:0;
    }

    static public function newNumberPlane($data){
        $scenCount = (self::readScenePlanes($data['scen_number'],$data['proj_id'])[1]->rowCount());
        if($data['plan_number']<=$scenCount){
            $query = 'SELECT plan_id,plan_number,scen_id FROM planes WHERE plan_number>=:plan_number AND scen_id = :scen_id  ORDER BY plan_number ASC';
            //echo json_encode($data,JSON_UNESCAPED_SLASHES);
            self::numberChalenger($query,$data,true);
        }
    }
    
    static public function updateNumberPlane($data,$preScen_id){
        if($data['scen_number']<$preScen_id){//Movimiento de mayor a menor
            $query = 'SELECT scen_id,scen_number,proj_id FROM scenes WHERE (scen_number BETWEEN :scen_number AND '.($preScen_id-1).') AND proj_id=:proj_id ORDER BY scen_number ASC';
            self::numberChalenger($query,$data,true);
        }
        if($data['scen_number']>$preScen_id){//Movimiento de menor a mayor
            $query = 'SELECT scen_id,scen_number,proj_id FROM scenes WHERE (scen_number BETWEEN '.($preScen_id+1).' AND :scen_id) AND proj_id=:proj_id  ORDER BY scen_number ASC';
            //echo json_encode($data,JSON_UNESCAPED_SLASHES);
            self::numberChalenger($query,$data,false);
        }
    }

    
    static public function deleteNumberPlane($data){
        $scenCount = (self::readScenePlanes($data['proj_id'])[1]->rowCount());
        $query = 'SELECT scen_id,scen_number,proj_id FROM scenes WHERE scen_number>:scen_number AND proj_id = :proj_id  ORDER BY scen_number ASC';
        //echo json_encode($data,JSON_UNESCAPED_SLASHES);
        $changePlanes = self::executeQuery($query,1,$data)[1]->fetchAll(PDO::FETCH_ASSOC);
        self::numberChalenger($query,$data,false);
    }

    static public function numberChalenger($query,$data,$way){
        $changePlanes = self::executeQuery($query,1,$data)[1]->fetchAll(PDO::FETCH_ASSOC);
        foreach($changePlanes as $plane){
            if($way){
                $plane['plan_number']++;
            }
            else{
                $plane['plan_number']--;
            }
            self::updateMethod($plane);
        }
    }

    
    static private function updateMethod($data) {
        $fields = array(
            "plan_id",
            "plan_number",
            "plan_duration",
            "plan_description",
            "plan_image",
            "shot_id",
            "move_id",
            "fram_id",
            "scen_id");

        if(!array_key_exists('plan_id',$data)){
            $data['plan_id'] = self::exist($data);
        }
        $data['scen_id'] = SceneModel::exist($data);
        $proj_id = SceneModel::getProjectId($data);
        if($data['plan_id']!=0){
            ProjectModel::makeUpdate($proj_id);
            if(!SceneModel::exist($data)) throw new Exception(419);
            $query = "UPDATE planes SET ";
            $dataAO = new ArrayObject($data);
            $iter = $dataAO->getIterator();
            while($iter->valid()){
                if(is_numeric($iter->key())){ 
                    $iter->next();
                    continue;
                }
                $query .= $iter->key()."=:".$iter->key();
                $iter->next();
                if($iter->valid()){
                    $query .= ",";
                }
                else{
                    $query .= " WHERE plan_id=:plan_id";
                }
            }
            return self::executeQuery($query,503,$data);
        }
        return 519;
    }

    static public function  executeQuery($query,$confirmCod = 0,$data=null,$fetch=false){
        $fields = array(
            "plan_id",
            "plan_number",
            "plan_duration",
            "plan_description",
            "plan_image",
            "shot_id",
            "move_id",
            "fram_id",
            "scen_id");
        $statement= Connection::doConnection()->prepare($query);
        if(isset($data)){
            foreach(array_keys($fields) as $index){
                $pattern = '/^.*:'.$fields[$index].'.*$/';
                $result = (preg_match($pattern,$query));
                if(!$result) continue;
                switch($index){
                    case 0:
                        $statement->bindParam(":plan_id", $data["plan_id"],PDO::PARAM_INT);
                        break;
                    case 1:
                        $statement->bindParam(":plan_number", $data["plan_number"],PDO::PARAM_INT);
                        break;
                    case 2:
                        $statement->bindParam(":plan_duration", $data["plan_duration"],PDO::PARAM_INT);
                        break;
                    case 3:
                        $statement->bindParam(":plan_description", $data["plan_description"],PDO::PARAM_STR);
                        break;
                    case 4:
                        $statement->bindParam(":plan_image", $data["plan_image"],PDO::PARAM_STR);
                        break;
                    case 5:
                        $statement->bindParam(":shot_id", $data["shot_id"],PDO::PARAM_INT);
                        break;
                    case 6:
                        $statement->bindParam(":move_id", $data["move_id"],PDO::PARAM_INT);
                        break;
                    case 7:
                        $statement->bindParam(":fram_id", $data["fram_id"],PDO::PARAM_INT);
                        break;
                    case 8:
                        $statement->bindParam(":scen_id", $data["scen_id"],PDO::PARAM_INT);
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