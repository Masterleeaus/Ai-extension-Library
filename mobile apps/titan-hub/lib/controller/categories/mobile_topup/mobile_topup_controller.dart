import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/routes/routes.dart';

import '../../../backend/model/auth/registation/basic_data_model.dart';
import '../../../backend/model/categories/topup/detect_operator_model.dart';
import '../../../backend/model/categories/topup/topup_info_model.dart';
import '../../../backend/model/common/common_success_model.dart';
import '../../../backend/services/api_services.dart';
import '../../../backend/utils/custom_snackbar.dart';
import '../remaining_balance_controller/ramaining_controller.dart';

class MobileTopupController extends GetxController {
  RxString billMethodselected = "".obs;
  RxString countryCode = ''.obs;
  RxString isoCode = ''.obs;
  RxString type = "".obs;
  RxString selectTopUpType = "MANUAL".obs;
  List<String> billList = [];
  List<String> topUpTypeList = ['AUTOMATIC', 'MANUAL'];
  final mobileNumberController = TextEditingController();
  final amountController = TextEditingController();
  final remainingController = Get.put(RemaingBalanceController());
  RxDouble dailyLimit = 0.0.obs;
  RxDouble monthlyLimit = 0.0.obs;
  RxDouble fee = 0.0.obs;
  RxDouble limitMin = 0.0.obs;
  RxDouble limitMax = 0.0.obs;
  RxDouble percentCharge = 0.0.obs;
  RxDouble fixedCharge = 0.0.obs;
  RxDouble exchangeRate2 = 0.0.obs;
  RxDouble rate = 0.0.obs;
  RxDouble totalFee = 0.0.obs;
  RxString baseCurrency = "".obs;
  RxDouble feesAndCharge = 0.0.obs;

  /// Automatic mobile top up
  RxDouble operatorLimitMin = 0.0.obs;
  RxDouble operatorLimitMax = 0.0.obs;
  RxDouble operatorPercentCharge = 0.0.obs;
  RxDouble operatorFixedCharge = 0.0.obs;
  RxDouble operatorFee = 0.0.obs;
  RxDouble operatorRate = 0.0.obs;
  RxDouble operatorTotalFee = 0.0.obs;
  RxString operatorCurrency = "".obs;
  RxString operatorId = "".obs;

  RxBool isGettingOperator = false.obs;
  RxInt selectedAmount = (-1).obs;

  @override
  void onInit() {
    amountController.text = "0";
    getTopUpInfoData();
    super.onInit();
  }

  final _isLoading = false.obs;

  bool get isLoading => _isLoading.value;

  late TopUpInfoModel _topUpInfoData;

  TopUpInfoModel get topUpInfoData => _topUpInfoData;

  // --------------------------- Api function ----------------------------------

  // get bill pay data function
  Future<TopUpInfoModel> getTopUpInfoData() async {
    _isLoading.value = true;
    update();

    await ApiServices.topupInfoApi().then(
      (value) {
        _topUpInfoData = value!;
        getBasicData();

        baseCurrency.value = _topUpInfoData.data.baseCurr;
        limitMin.value = _topUpInfoData.data.topupCharge.minLimit;
        limitMax.value = _topUpInfoData.data.topupCharge.maxLimit;
        percentCharge.value = _topUpInfoData.data.topupCharge.percentCharge;
        fixedCharge.value = _topUpInfoData.data.topupCharge.fixedCharge;
        dailyLimit.value = _topUpInfoData.data.topupCharge.dailyLimit;

        monthlyLimit.value = _topUpInfoData.data.topupCharge.monthlyLimit;
        rate.value = _topUpInfoData.data.baseCurrRate;
        billMethodselected.value = _topUpInfoData.data.topupTypes.first.name;
        for (var element in _topUpInfoData.data.topupTypes) {
          billList.add(element.name);
        }

        //start remaing get
        remainingController.transactionType.value =
            _topUpInfoData.data.getRemainingFields.transactionType;
        remainingController.attribute.value =
            _topUpInfoData.data.getRemainingFields.attribute;
        remainingController.cardId.value = _topUpInfoData.data.topupCharge.id;
        remainingController.senderAmount.value = amountController.text;
        remainingController.senderCurrency.value = _topUpInfoData.data.baseCurr;
        remainingController.extraRate.value = 1.0;
        remainingController.getRemainingBalanceProcess();
        update();
      },
    ).catchError(
      (onError) {
        log.e(onError);
      },
    );
    update();
    return _topUpInfoData;
  }

  late BasicDataModel _basicDataModel;

  BasicDataModel get basicDataModel => _basicDataModel;

  Future<BasicDataModel> getBasicData() async {
    update();

    // calling  from api service
    await ApiServices.basicData().then((value) {
      _basicDataModel = value!;
      countryCode.value =
          _basicDataModel.data.countries.first.mobileCode.toString();
      isoCode.value = _basicDataModel.data.countries.first.iso2.toString();
      _isLoading.value = false;
      update();
    }).catchError(
      (onError) {
        log.e(onError);
      },
    );
    update();
    return _basicDataModel;
  }

  final _isInsertLoading = false.obs;

  bool get isInsertLoading => _isInsertLoading.value;

  late CommonSuccessModel _successDatya;

  CommonSuccessModel get successDatya => _successDatya;

  Future<CommonSuccessModel> topUpApiProcess({
    required String type,
    required String amount,
    required String number,
  }) async {
    _isInsertLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'topup_type': type,
      'mobile_number': number,
      'amount': amount,
      'currency': baseCurrency.value,
      'mobile_code': countryCode.value,
    };

    // calling login api from api service
    await ApiServices.topupConfirmedApi(body: inputBody).then((value) {
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

  Future<CommonSuccessModel> mobileTopUpAutomaticProcess() async {
    _isInsertLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'operator_id': operatorId.value,
      'mobile_code': countryCode.value,
      'mobile_number': mobileNumberController.text,
      'country_code': isoCode.value,
      'amount': amountController.text,
    };

    await ApiServices.topUpAutomaticConfirmedApi(body: inputBody).then((value) {
      _successDatya = value!;
      _isInsertLoading.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isInsertLoading.value = false;
    update();
    return _successDatya;
  }

  String? getType(String value) {
    for (var element in topUpInfoData.data.topupTypes) {
      if (element.name == value) {
        debugPrint(element.id.toString());
        debugPrint(element.name);
        debugPrint("Done");
        return element.id.toString();
      }
    }
    return null;
  }

  void gotoMobilePreview(BuildContext context) {
    Get.toNamed(Routes.mobilePreviewScreen);
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
    feesAndCharge.value = operatorFixedCharge.value * rate;
    double value = operatorFixedCharge.value * rate;
    value = value +
        (double.parse(
                amountController.text.isEmpty ? '0.0' : amountController.text) *
            (operatorPercentCharge.value / 100));

    if (amountController.text.isEmpty) {
      operatorTotalFee.value = 0.0;
    } else {
      operatorTotalFee.value = value;
    }

    debugPrint(operatorTotalFee.value.toStringAsPrecision(2));
    return operatorTotalFee;
  }

  final _isDetectLoading = false.obs;

  bool get isDetectLoading => _isDetectLoading.value;

  late DetectOperatorModel _detectOperatorModel;

  DetectOperatorModel get detectOperatorModel => _detectOperatorModel;

  var maxLimitClc = '';
  var minLimitClc = '';

  // --------------------------- Api function ----------------------------------
  // get bill pay data function
  Future<DetectOperatorModel> getDetectOperator() async {
    _isDetectLoading.value = true;
    update();

    await ApiServices.getDetectOperator(
      mobileNumber: mobileNumberController.text,
      countryCode: isoCode.value,
      mobileCode: countryCode.value,
    ).then((value) {
      _detectOperatorModel = value!;
      exchangeRate2.value = _topUpInfoData.data.baseCurrRate /
          _detectOperatorModel.data.data!.receiverCurrencyRate;
      dailyLimit.value =
          _topUpInfoData.data.topupCharge.dailyLimit / exchangeRate2.value;

      monthlyLimit.value =
          _topUpInfoData.data.topupCharge.monthlyLimit / exchangeRate2.value;

      remainingController.transactionType.value =
          _topUpInfoData.data.getRemainingFields.transactionType;
      remainingController.attribute.value =
          _topUpInfoData.data.getRemainingFields.attribute;
      remainingController.cardId.value = _topUpInfoData.data.topupCharge.id;
      remainingController.senderAmount.value = amountController.text;
      remainingController.senderCurrency.value = _topUpInfoData.data.baseCurr;
      remainingController.extraRate.value = exchangeRate2.value;
      remainingController.getRemainingBalanceProcess();

      if (_detectOperatorModel.data.status == false) {
        CustomSnackBar.error(_detectOperatorModel.data.message);
        isGettingOperator.value = false;
      } else {
        var data = _detectOperatorModel.data.data;
        var minAmount = 0.0;
        var maxAmount = 0.0;
        if (data!.supportsLocalAmounts == true &&
            data.destinationCurrencyCode == data.senderCurrencyCode &&
            data.localMinAmount == null &&
            data.localMaxAmount == null) {
          minAmount = double.parse(data.minAmount.toString());
          maxAmount = double.parse(data.maxAmount.toString());
        } else if (data.supportsLocalAmounts == true &&
            data.localMinAmount != null &&
            data.localMaxAmount != null) {
          minAmount = double.parse(data.localMinAmount.toString());
          maxAmount = double.parse(data.localMaxAmount.toString());
        } else {
          minAmount = double.parse(data.minAmount.toString());
          maxAmount = double.parse(data.maxAmount.toString());
        }
        if (minAmount.isNaN || maxAmount.isNaN) {
          CustomSnackBar.error("Something went wrong");
          isGettingOperator.value = false;
        } else {
          if (data.supportsLocalAmounts == true) {
            minLimitClc = minAmount.toStringAsFixed(2);
            maxLimitClc = maxAmount.toStringAsFixed(2);
          } else {
            var fxRate = data.fx?.rate;
            minLimitClc = (minAmount * fxRate).toStringAsFixed(2);
            maxLimitClc = (maxAmount * fxRate).toStringAsFixed(2);
          }
          debugPrint("test 1");
          operatorCurrency.value = data.receiverCurrencyCode!;
          debugPrint("test 2");
          operatorLimitMin.value = double.parse(minLimitClc.toString());
          debugPrint("test 3");
          operatorLimitMax.value = double.parse(maxLimitClc.toString());
          debugPrint("test 4");
          operatorPercentCharge.value =
              data.fees!.internationalPercentage ?? 0.0;
          debugPrint("test 5");
          operatorFixedCharge.value = data.fees!.international ?? 0.0;
          debugPrint("test 6");
          operatorRate.value = data.receiverCurrencyRate ?? 0.0;
          debugPrint("test 7");
          operatorId.value = data.operatorId.toString();
          debugPrint("test 8");
          isGettingOperator.value = true;
          debugPrint("test 9");
        }
      }
      // var data = _detectOperatorModel.data.data;
      //
      // if (_detectOperatorModel.data.status == false) {
      //   CustomSnackBar.error(_detectOperatorModel.data.message);
      //   isGettingOperator.value = false;
      // } else {
      //   debugPrint("💐🌈🏳️‍🌈 test 1");
      //   operatorCurrency.value = data!.receiverCurrencyCode!;
      //   debugPrint("💐🌈🏳️‍🌈 test 2");
      //   operatorLimitMin.value = data.localMinAmount ?? data.minAmount;
      //   debugPrint("💐🌈🏳️‍🌈 test 3");
      //   operatorLimitMax.value = data.localMaxAmount ?? data.maxAmount;
      //   debugPrint("💐🌈🏳️‍🌈 test 4");
      //   operatorPercentCharge.value = data.fees!.internationalPercentage ?? 0.0;
      //   debugPrint("test 5");
      //   operatorFixedCharge.value = data.fees!.international ?? 0.0;
      //   debugPrint("test 6");
      //   operatorRate.value = data.receiverCurrencyRate ?? 0.0;
      //   debugPrint("test 7");
      //   operatorId.value = data.operatorId.toString();
      //   debugPrint("test 8");
      //   isGettingOperator.value = true;
      //   debugPrint("test 9");
      // }
      update();
    }).catchError((onError) {
      log.e(onError);
      isGettingOperator.value = false;
    });

    update();
    return _detectOperatorModel;
  }
}
