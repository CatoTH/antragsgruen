<?php


class OdsTemplateEngine extends OOfficeTemplateEngine{

    /** @var DOMDocument */
    protected $doc = null;

    /**
     * @param string[]
     * @param string
     * @return string
     */
    public function convert($antrag_absaetze, $begruendung)
    {
        $this->appendStyleNode("Antragsgruen_fett", array(
            "fo:font-weight"            => "bold",
            "style:font-weight-asian"   => "bold",
            "style:font-weight-complex" => "bold",
        ));
        $this->appendStyleNode("Antragsgruen_kursiv", array(
            "fo:font-style"            => "italic",
            "style:font-style-asian"   => "italic",
            "style:font-style-complex" => "italic",
        ));
        $this->appendStyleNode("Antragsgruen_unterstrichen", array(
            "style:text-underline-width" => "auto",
            "style:text-underline-color" => "font-color",
            "style:text-underline-style" => "solid",
        ));
        $this->appendStyleNode("Antragsgruen_gruen", array(
            "fo:color" => "#00ff00",
        ));
        $this->appendStyleNode("Antragsgruen_rot", array(
            "fo:color" => "#ff0000",
        ));

        return $this->doc->saveXML();
    }
}
