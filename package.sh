#!/bin/bash
tar -zchvf Presta_Invipay_Paygate.tgz --exclude='.DS_Store' invipaypaygate
zip -r invipaypaygate.zip invipaypaygate -x *.DS_Store*