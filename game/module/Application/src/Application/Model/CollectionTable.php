<?php
namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Session\Container;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Where;


class CollectionTable
{

    protected $tableGateway;

    protected $userId;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    /**
     * @Description Fetch all data from Database
     *
     * @return resultset
     */
    public function getCollectionList()
    {
        $resultSet = $this->tableGateway->select();
        return $resultSet->toArray();
    }

    /**
     * @Description attachmentd in document table in database
     */
    public function saveDocument(Document $Document)
    {
        $session = new Container('User');
        if ($session->offsetExists('userId')) {
            $userId = $this->getSesionUserId();
        }
        $data = array(
            'tag_name' => $Document->tag_name,
            'file_name' => $Document->file_name,
            'module_id' => $Document->module_id,
            'module_name' => $Document->module_name,
            'category_id' => $Document->category_id,
            'file_path' => $Document->file_path,
            'updated_by' => $userId
        );
        
        $id = (int) $Document->id;
        
        try {
            if ($id == 0) {
                $data['created_at'] = date("Y-m-d H:i:s");
                $data['created_by'] = $userId;
                $this->tableGateway->insert($data);
            } else {
                if ($this->getDocument($id)) {
                    $this->tableGateway->update($data, array(
                        'id' => $id
                    ));
                } else {
                    throw new \Exception('Document id does not exist');
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getPrevious()->getMessage());
        }
    }

    public function fetchModuleDocumnets(Document $Document)
    {
        
        
        $resultSet = $this->tableGateway->select(array(
            'module_id' => $Document->module_id,
            'module_name' => $Document->module_name,
            'is_deleted' => 'n'
        ));
        //prx($resultSet->toArray());
        return $resultSet;
    }
    
    public function fetchIndividualDocumnets($docId='',$moduleId='')
    {
        $resultSet = $this->tableGateway->select(array(
            'id' => $docId,
            'module_name' => $moduleId,
            'is_deleted' => 'n'
        ));
        return $resultSet;
    }
    
    public function fetchAllDocumnetsByModuleId($moduleId='', $moduleName='')
    {
        $resultSet = $this->tableGateway->select(array(
            'module_id' => $moduleId,
            'module_name' => $moduleName,
            'is_deleted' => 'n'
        ));
        return $resultSet;
    }
    
    
    public function fetchAllDocumnets($moduleId='', $moduleName='')
    {
        $sql = new Sql($this->getDbAdapter());
        $select = $sql->select()->from(array('doc' => 'documents'));
        $select->columns(array(
            '*'
        ));
        $select->where(array(
            'module_id' => $moduleId,
            'module_name' => $moduleName,
            'is_deleted' => 'n'
        ));
        $select->order("doc.created_at DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $this->resultSetPrototype()->initialize($statement->execute())->toArray();
         $arr1 = array();
         $arr2 = array();
        foreach ($result as $key=>$val) {
            $pathPart=pathinfo($val['file_path']);
            $extension = strtoupper($pathPart['extension']);
           $imageArray =  array("ANI","BMP","CAL","FAX","GIF","IMG","JBG","JPE","JPEG","JPG","MAC","PBM","PCD","PCX","PCT","PGM","PNG","PPM","PSD","RAS","TGA","TIFF","WMF");
            if(in_array($extension, $imageArray)) {
                $arr1[] = $val;
            }
            else {
                $arr2[] = $val;
            }
        }
        if(!empty($arr1)) {
            return $arr1;
        }
        if(!empty($arr2)) {
            return $arr2;
        }
    }
    

    public function deleteDocument($id, $userId = NULL)
    {
        $data['updated_by'] = $userId;
        $data['is_deleted'] = 'y';
        try {
            $this->tableGateway->update($data, array(
                'id' => $id
            ));
        } catch (\Exception $e) {
            throw new \Exception($e->getPrevious()->getMessage());
        }
    }
    /**
     * Function to fetch event data to populate on home screen Carousel
     * @author Vikash kumar
     * @throws \Exception
     */
    public function fetchEventData()
    {
        try {
            $select = $this->tableGateway->getSql()->select();
            $select->columns(array(
                'id',
                'tag_name',
                'module_id',
                'file_name',
                'file_path',
            ));
         
            $select->join('event', " documents.module_id = event.id", 
                array('title','is_home','published_date','end_date','status'), 'left');
            $select->where(array(
                'event.is_home' => '1',
                'documents.module_name' => 'event',
                'gatekeeper_status' => 'Approved',
                'event.is_deleted' => 'n',
                'documents.is_deleted' => 'n'
            ));
            $select->group('documents.module_id');
            $sqlStmt = $select->getSqlString();
            $sqlStmt = str_replace('"', '', $sqlStmt);
//             echo $sqlStmt; die;
            $statement = $this->getDbAdapter()
                ->query($sqlStmt, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE)
                ->toArray();
            return $statement;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    /**
     * Function to get social data
     * @author Vikash kumar
     * @throws \Exception
     */
    public function fetchSocialData($id,$limit=false,$orderBy='id',$orderType='desc')
    {        
        
        $sql = new Sql($this->getDbAdapter());
        $select = $sql->select()
        ->from(array(
            'documents' => 'documents'
        ))->limit(3)
        ->columns(array(
                'id',
                'tag_name',
                'module_id',
                'file_name',
                'file_path',
         ))
         ->join('social_activities', " documents.module_id = social_activities.id",
             array('title','event_date_time','is_deleted'), 'left')
//          ->join('social_activity_comments', " social_activity_comments.comment_parent_id = social_activities.id",
//                 array('comment_parent_id','totalCount' => new Expression('COUNT(*)')), 'left')
         ->where(array(
                'documents.module_name' => 'social_activity',
                'social_activities.is_deleted' => 'n',
                'documents.is_deleted' => 'n',
                'documents.module_id' => $id,
            ))
            ->order(array(
            $orderBy => $orderType
        ));
        if($limit)
        {
            $select->limit($limit);
        }
        //echo $select->getSqlString(); die;        
        $statement = $sql->prepareStatementForSqlObject($select);
         
        return $result = $this->resultSetPrototype()
        ->initialize($statement->execute())
        ->toArray();
    }
    
    
}
