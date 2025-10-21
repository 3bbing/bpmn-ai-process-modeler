<?php

namespace App\Services;

use DOMDocument;

class BpmnGenerationService
{
    public function generate(array $model, ?string $title = null): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $definitions = $doc->createElementNS('http://www.omg.org/spec/BPMN/20100524/MODEL', 'bpmn:definitions');
        $definitions->setAttribute('id', 'Definitions_'.uniqid());
        $definitions->setAttribute('targetNamespace', 'http://bpmnai.local/process');
        $doc->appendChild($definitions);

        $process = $doc->createElement('bpmn:process');
        $process->setAttribute('id', 'Process_'.uniqid());
        $process->setAttribute('isExecutable', 'false');
        if ($title) {
            $process->setAttribute('name', $title);
        }
        $definitions->appendChild($process);

        $laneSet = $doc->createElement('bpmn:laneSet');
        $laneSet->setAttribute('id', 'LaneSet_'.uniqid());
        $process->appendChild($laneSet);

        $laneIndex = [];
        foreach ($model['lanes'] ?? [] as $laneName) {
            $laneId = 'Lane_'.md5($laneName);
            $laneIndex[$laneName] = $laneId;
            $lane = $doc->createElement('bpmn:lane');
            $lane->setAttribute('id', $laneId);
            $lane->setAttribute('name', $laneName);
            $laneSet->appendChild($lane);
        }

        $elementIndex = [];

        foreach ($model['events'] ?? [] as $event) {
            $id = $event['id'];
            $type = $event['type'];
            $element = $doc->createElement('bpmn:'.($type === 'start' ? 'startEvent' : 'endEvent'));
            $element->setAttribute('id', $id);
            if (! empty($event['label'])) {
                $element->setAttribute('name', $event['label']);
            }
            $process->appendChild($element);
            $elementIndex[$id] = $element;
        }

        foreach ($model['tasks'] ?? [] as $task) {
            $taskElement = $doc->createElement('bpmn:task');
            $taskElement->setAttribute('id', $task['id']);
            $taskElement->setAttribute('name', $task['label'] ?? '');
            $process->appendChild($taskElement);
            $elementIndex[$task['id']] = $taskElement;

            if (isset($laneIndex[$task['lane']])) {
                $laneElement = $laneSet->getElementsByTagName('lane');
                foreach ($laneElement as $laneNode) {
                    if ($laneNode->getAttribute('id') === $laneIndex[$task['lane']]) {
                        $flowRef = $doc->createElement('bpmn:flowNodeRef', $task['id']);
                        $laneNode->appendChild($flowRef);
                    }
                }
            }
        }

        foreach ($model['gateways'] ?? [] as $gateway) {
            $gatewayElement = $doc->createElement('bpmn:exclusiveGateway');
            $gatewayElement->setAttribute('id', $gateway['id']);
            $gatewayElement->setAttribute('name', $gateway['label'] ?? '');
            $process->appendChild($gatewayElement);
            $elementIndex[$gateway['id']] = $gatewayElement;
        }

        foreach ($model['artifacts'] ?? [] as $artifact) {
            $artifactElement = $doc->createElement('bpmn:dataObjectReference');
            $artifactElement->setAttribute('id', $artifact['id']);
            $artifactElement->setAttribute('name', $artifact['label'] ?? '');
            if (! empty($artifact['attachedTo'])) {
                $artifactElement->setAttribute('dataObjectRef', $artifact['attachedTo']);
            }
            $process->appendChild($artifactElement);
            $elementIndex[$artifact['id']] = $artifactElement;
        }

        foreach ($model['flows'] ?? [] as $index => $flow) {
            [$source, $target] = $flow;
            $sequenceFlow = $doc->createElement('bpmn:sequenceFlow');
            $sequenceFlow->setAttribute('id', 'Flow_'.$index);
            $sequenceFlow->setAttribute('sourceRef', $source);
            $sequenceFlow->setAttribute('targetRef', $target);
            $process->appendChild($sequenceFlow);
        }

        return $doc->saveXML();
    }
}
