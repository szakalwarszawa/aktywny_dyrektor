<?php

namespace Parp\MainBundle\Entity;
use APY\DataGridBundle\Grid\Mapping as GRID;
use Doctrine\ORM\Mapping as ORM;

/**
 * Zasoby
 *
 * @ORM\Table(name="plik")
 * @ORM\Entity
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, nazwa, typ, opis, file")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class Plik
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
    */
    private $deletedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="nazwa", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $nazwa;
    
    /**
     * @var string
     *
     * @ORM\Column(name="typ", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $typ;

    /**
     * @var string
     *
     * @ORM\Column(name="opis", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $opis;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="file", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $file;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="obiekt", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $obiekt;
    /**
     * @var string
     *
     * @ORM\Column(name="obiektId", type="integer")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $obiektId;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Plik
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Set nazwa
     *
     * @param string $nazwa
     *
     * @return Plik
     */
    public function setNazwa($nazwa)
    {
        $this->nazwa = $nazwa;

        return $this;
    }

    /**
     * Get nazwa
     *
     * @return string
     */
    public function getNazwa()
    {
        return $this->nazwa;
    }

    /**
     * Set opis
     *
     * @param string $opis
     *
     * @return Plik
     */
    public function setOpis($opis)
    {
        $this->opis = $opis;

        return $this;
    }

    /**
     * Get opis
     *
     * @return string
     */
    public function getOpis()
    {
        return $this->opis;
    }

    /**
     * Set file
     *
     * @param string $file
     *
     * @return Plik
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file
     *
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set obiekt
     *
     * @param string $obiekt
     *
     * @return Plik
     */
    public function setObiekt($obiekt)
    {
        $this->obiekt = $obiekt;

        return $this;
    }

    /**
     * Get obiekt
     *
     * @return string
     */
    public function getObiekt()
    {
        return $this->obiekt;
    }

    /**
     * Set obiektId
     *
     * @param integer $obiektId
     *
     * @return Plik
     */
    public function setObiektId($obiektId)
    {
        $this->obiektId = $obiektId;

        return $this;
    }

    /**
     * Get obiektId
     *
     * @return integer
     */
    public function getObiektId()
    {
        return $this->obiektId;
    }
    
    public function upload()
    {
        // the file property can be empty if the field is not required
        if (null === $this->getFile()) {
            return;
        }
    
        // use the original file name here but you should
        // sanitize it at least to avoid any security issues
    
        // move takes the target directory and then the
        // target filename to move to
        $guid = uniqid();
		//print $this->getFile();
		$fname = $guid."_".$this->getFile()->getClientOriginalName();
        $this->getFile()->move($this->getUploadRootDir(),$fname);
    
        // set the path property to the filename where you've saved the file
        $this->path = $guid."_".$this->getFile()->getClientOriginalName();
    
        // clean up the file property as you won't need it anymore
        $this->file = null;
        $this->setFile($fname);
    }
    
    
    public function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../../work/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads';
    }
    public function getFilePath(){
        return $this->getUploadRootDir()."/".$this->getFile();
    }

    /**
     * Set typ
     *
     * @param string $typ
     *
     * @return Plik
     */
    public function setTyp($typ)
    {
        $this->typ = $typ;

        return $this;
    }

    /**
     * Get typ
     *
     * @return string
     */
    public function getTyp()
    {
        return $this->typ;
    }
}
