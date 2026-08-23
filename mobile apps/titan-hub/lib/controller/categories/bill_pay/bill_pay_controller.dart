import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/routes/routes.dart';

import '../../../backend/model/categories/bill_pay_model/bill_pay_model.dart';
import '../../../backend/model/common/common_success_model.dart';
import '../../../backend/services/api_services.dart';
import '../../../backend/utils/logger.dart';
import '../remaining_balance_controller/ramaining_controller.dart';

final log = logger(BillPayController);

class BillPayController extends GetxController {
  RxString billMethodselected = "".obs;
  RxString selectedBillMonths = "".obs;
  RxString type = "".obs;
  List<BillType> billList = [];
  List<String> billMonthsList = [];

  final billNumberController = TextEditingController();
  final amountController = TextEditingController();
  final remainingController = Get.put(RemaingBalanceController());
  RxDouble dailyLimit = 0.0.obs;
  RxDouble monthlyLimit = 0.0.obs;
  RxDouble fee = 0.0.obs;
  RxDouble limitMin = 0.0.obs;
  RxDouble limitMax = 0.0.obs;
  RxDouble automaticLimitMin = 0.0.obs;
  RxDouble automaticLimitMax = 0.0.obs;
  RxDouble percentCharge = 0.0.obs;
  RxDouble automaticCharge = 0.0.obs;
  RxDouble fixedCharge = 0.0.obs;
  RxDouble rate = 0.0.obs;
  RxDouble exchangeRate2 = 0.0.obs;

  RxDouble automaticRate = 0.0.obs;
  RxDouble totalFee = 0.0.obs;
  RxDouble exchangeRate = 0.0.obs;
  RxDouble automaticTotalFee = 0.0.obs;
  RxString baseCurrency = "".obs;
  RxString selectedCurrency = "".obs;
  RxString automaticSelectedCurrency = "".obs;
  RxBool isAutomatic = false.obs;

  @override
  void onInit() {
    amountController.text = "0";
    getBillPayInfoData();
    super.onInit();
  }

  final _isLoading = false.obs;

  bool get isLoading => _isLoading.value;

  final _isInsertLoading = false.obs;

  bool get isInsertLoading => _isInsertLoading.value;

  late BillPayInfoModel _billPayInfoData;

  BillPayInfoModel get billPayInfoData => _billPayInfoData;

  // --------------------------- Api function ----------------------------------
  // get bill pay data function
  Future<BillPayInfoModel> getBillPayInfoData() async {
    _isLoading.value = true;
    update();

    await ApiServices.billPayInfoAPi().then((value) {
      _billPayInfoData = value!;
      var data = _billPayInfoData.data;
      baseCurrency.value = data.baseCurr;
      limitMin.value = data.billPayCharge.minLimit;
      limitMax.value = data.billPayCharge.maxLimit;
      exchangeRate2.value = _billPayInfoData.data.baseCurrRate /
          _billPayInfoData.data.billTypes.first.receiverCurrencyRate;
      automaticLimitMin.value =
          _billPayInfoData.data.billTypes.first.minLocalTransactionAmount;
      automaticLimitMax.value =
          _billPayInfoData.data.billTypes.first.maxLocalTransactionAmount;
      percentCharge.value = data.billPayCharge.percentCharge;

      dailyLimit.value =
          _billPayInfoData.data.billPayCharge.dailyLimit / exchangeRate2.value;

      monthlyLimit.value = _billPayInfoData.data.billPayCharge.monthlyLimit /
          exchangeRate2.value;
      fixedCharge.value = data.billPayCharge.fixedCharge;
      rate.value = data.baseCurrRate;
      automaticRate.value = data.billTypes.first.receiverCurrencyRate;
      automaticSelectedCurrency.value =
          data.billTypes.first.receiverCurrencyCode;

      if (data.billTypes.first.itemType == "AUTOMATIC") {
        isAutomatic.value = true;
      } else {
        isAutomatic.value = false;
      }

      billMethodselected.value = data.billTypes.first.name!;

      for (var element in data.billTypes) {
        billList.add(
          BillType(
            id: element.id,
            name: element.name,
            countryCode: element.countryCode,
            countryName: element.countryName,
            type: element.type,
            serviceType: element.serviceType,
            minLocalTransactionAmount: element.minLocalTransactionAmount,
            maxLocalTransactionAmount: element.maxLocalTransactionAmount,
            localTransactionFee: element.localTransactionFee,
            localTransactionFeeCurrencyCode:
                element.localTransactionFeeCurrencyCode,
            localTransactionFeePercentage:
                element.localTransactionFeePercentage,
            denominationType: element.denominationType,
            itemType: element.itemType,
            slug: element.slug,
            receiverCurrencyRate: element.receiverCurrencyRate,
            receiverCurrencyCode: element.receiverCurrencyCode,
          ),
        );
      }

      selectedCurrency.value = data.billTypes.first.receiverCurrencyCode!;
      selectedBillMonths.value =
          _billPayInfoData.data.billMonths.first.fieldName;
      for (var element in _billPayInfoData.data.billMonths) {
        billMonthsList.add(element.fieldName);
      }

      //start remaing get
      remainingController.transactionType.value =
          _billPayInfoData.data.getRemainingFields.transactionType;
      remainingController.attribute.value =
          _billPayInfoData.data.getRemainingFields.attribute;
      remainingController.cardId.value = _billPayInfoData.data.billPayCharge.id;
      remainingController.senderAmount.value = amountController.text;
      remainingController.senderCurrency.value = _billPayInfoData.data.baseCurr;
      remainingController.extraRate.value = exchangeRate2.value;
      remainingController.getRemainingBalanceProcess();
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _billPayInfoData;
  }

  late CommonSuccessModel _successDatya;

  CommonSuccessModel get successDatya => _successDatya;

  // Login process function
  Future<CommonSuccessModel> billPayApiProcess({
    required String type,
    required String amount,
    required String billNumber,
  }) async {
    _isInsertLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'bill_type': type,
      'bill_number': billNumber,
      'amount': amount,
      'bill_month': selectedBillMonths.value,
      'biller_item_type': 'AUTOMATIC',
    };
    // calling login api from api service
    await ApiServices.billPayConfirmedApi(body: inputBody).then((value) {
      _successDatya = value!;

      _isInsertLoading.value = false;
      update();

      // Get.offAllNamed(Routes.bottomNavBarScreen);
    }).catchError((onError) {
      log.e(onError);
    });

    _isInsertLoading.value = false;
    update();
    return _successDatya;
  }

  void gotoBillPreview(BuildContext context) {
    Get.toNamed(Routes.billPayPreviewScreen);
  }

  String? getType(String value) {
    for (var element in billPayInfoData.data.billTypes) {
      if (element.name == value) {
        return element.id.toString();
      }
    }
    return null;
  }

  RxDouble getFee({required double rate}) {
    double value = fixedCharge.value * rate;
    value = value +
        (double.parse(
                amountController.text.isEmpty ? '0.0' : amountController.text) *
            (percentCharge.value / 100));

    if (amountController.text.isEmpty) {
      totalFee.value = 0.0;
    } else {
      totalFee.value = value;
    }

    debugPrint(totalFee.value.toStringAsPrecision(2));
    return totalFee;
  }

  RxDouble getAutomaticFee({required double rate}) {
    double value = automaticCharge.value * rate;
    value = value +
        (double.parse(
                amountController.text.isEmpty ? '0.0' : amountController.text) *
            (percentCharge.value / 100));

    if (amountController.text.isEmpty) {
      automaticTotalFee.value = 0.0;
    } else {
      automaticTotalFee.value = value;
    }

    debugPrint(automaticTotalFee.value.toStringAsPrecision(2));
    return automaticTotalFee;
  }

  RxDouble getExchangeRate({required double r}) {
    double value = rate.value / r;
    if (value == 0.0) {
      exchangeRate.value = 0.0;
    } else {
      exchangeRate.value = value;
    }
    return exchangeRate;
  }
}
