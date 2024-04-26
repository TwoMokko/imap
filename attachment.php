<?php

//function getAttachments($imap, $mailNum, $part, $partNum) {
//    $attachments = array();
//
//    if (isset($part->parts)) {
//        foreach ($part->parts as $key => $subpart) {
//            if($partNum != "") {
//                $newPartNum = $partNum . "." . ($key + 1);
//            }
//            else {
//                $newPartNum = ($key+1);
//            }
//            $result = getAttachments($imap, $mailNum, $subpart,
//                $newPartNum);
//            if (count($result) != 0) {
//                array_push($attachments, $result);
//            }
//        }
//    }
//    else if (isset($part->disposition)) {
//        if ($part->disposition == "ATTACHMENT") {
//            $partStruct = imap_bodystruct($imap, $mailNum,
//                $partNum);
//            $attachmentDetails = array(
//                "name"    => $part->dparameters[0]->value,
//                "partNum" => $partNum,
//                "enc"     => $partStruct->encoding
//            );
//            return $attachmentDetails;
//        }
//    }
//
//    return $attachments;
//}


function getPartAttachment($imap, int $uid, stdClass $structure, string $section = ''): array {
    if (isset($structure->disposition) && $structure->disposition == 'attachment') {
        $partStruct = imap_bodystruct($imap, $uid, $section);
        $attachmentDetails = array(
            "name"    => $partStruct->dparameters[0]->value,
            "section" => $section,
            "enc"     => $partStruct->encoding
        );
        return $attachmentDetails;
//        $text = imap_fetchbody($imap, $uid, $section);
//        return imap_base64($text);
////        return mb_convert_encoding($text, 'UTF-8', 'KOI8-R');
////        return $text;
//        return mb_convert_encoding($text, 'UTF-8', 'KOI8-R');
//        return $text;
    }

    if (property_exists($structure, 'parts') && $structure->type == 1) {
        foreach ($structure->parts as $index => $subStruct)
        {
            $prefix = $section ? $section . '.' : '';
            if ($data = getPartAttachment($imap, $uid, $subStruct, $prefix . ($index + 1))) return $data;
        }
    }

    return [];
}