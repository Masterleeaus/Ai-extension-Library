import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/backend/model/categories/agent-moneyout/agent_money_info_model.dart';

import '../../../backend/model/categories/agent-moneyout/check_user_with_qr_code.dart';
import '../../../backend/model/common/common_success_model.dart';
import '../../../backend/services/api_services.dart';
import '../../../language/english.dart';
import '../remaining_balance_controller/ramaining_controller.dart';

class AgentMoneyOutController extends GetxController {
  final copyInputController = TextEditingController();
  final amountController = TextEditingController();
  final remainingController = Get.put(RemaingBalanceController());

  RxDouble dailyLimit = 0.0.obs;
  RxDouble monthlyLimit = 0.0.obs;
  RxDouble fee = 0.0.obs;
  RxDouble limitMin = 0.0.obs;
  RxDouble limitMax = 0.0.obs;
  RxDouble percentCharge = 0.0.obs;
  RxDouble fixedCharge = 0.0.obs;
  RxDouble rate = 0.0.obs;
  RxDouble totalFee = 0.0.obs;

  String enteredAmount = "";
  String transferFeeAmount = "";
  String totalCharge = "";
  String youWillGet = "";
  String payableAmount = "";
  RxString checkUserMessage = "".obs;
  RxBool isValidUser = false.obs;

  RxString baseCurrency = "".obs;
  List<String> baseCurrencyList = [];

  @override
  void onInit() {
    amountController.text = "0";
    getAgentMoneyInfoData();
    super.onInit();
  }

  // ---------------------------------------------------------------------------
  //                              Get agent money out Info Data
  // ---------------------------------------------------------------------------

  // -------------------------------Api Loading Indicator-----------------------
  //
  final _isLoading = false.obs;

  bool get isLoading => _isLoading.value;

  late AgentMoneyOutInfoModel _agentMoneyOutInfoModel;
  AgentMoneyOutInfoModel get agentMoneyOutInfoModel => _agentMoneyOutInfoModel;

  Future<AgentMoneyOutInfoModel> getAgentMoneyInfoData() async {
    _isLoading.value = true;
    update();

    await ApiServices.agentMoneyOutInfoApi().then((value) {
      _agentMoneyOutInfoModel = value!;
      baseCurrency.value = _agentMoneyOutInfoModel.data.baseCurr;
      baseCurrencyList.add(_agentMoneyOutInfoModel.data.baseCurr);

      limitMin.value = _agentMoneyOutInfoModel.data.moneyOutCharge.minLimit;
      limitMax.value = _agentMoneyOutInfoModel.data.moneyOutCharge.maxLimit;
      dailyLimit.value = _agentMoneyOutInfoModel.data.moneyOutCharge.dailyLimit;
      monthlyLimit.value =
          _agentMoneyOutInfoModel.data.moneyOutCharge.monthlyLimit;
      percentCharge.value =
          _agentMoneyOutInfoModel.data.moneyOutCharge.percentCharge;
      fixedCharge.value =
          _agentMoneyOutInfoModel.data.moneyOutCharge.fixedCharge;
      rate.value = _agentMoneyOutInfoModel.data.baseCurrRate;
      //start remaing get
      remainingController.transactionType.value =
          _agentMoneyOutInfoModel.data.getRemainingFields.transactionType;
      remainingController.attribute.value =
          _agentMoneyOutInfoModel.data.getRemainingFields.attribute;
      remainingController.cardId.value =
          _agentMoneyOutInfoModel.data.moneyOutCharge.id;
      remainingController.senderAmount.value = amountController.text;
      remainingController.senderCurrency.value =
          _agentMoneyOutInfoModel.data.baseCurr;
      remainingController.getRemainingBalanceProcess();

      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _agentMoneyOutInfoModel;
  }

  final _isCheckAgentLoading = false.obs;

  bool get isCheckUserLoading => _isCheckAgentLoading.value;

  late CommonSuccessModel _checkAgentExistModel;

  CommonSuccessModel get checkUserExistModel => _checkAgentExistModel;
  Future<CommonSuccessModel> getCheckUserExistDate() async {
    _isCheckAgentLoading.value = true;

    Map<String, dynamic> inputBody = {'email': copyInputController.text};
    update();

    await ApiServices.checkAgentExistApi(body: inputBody).then((value) {
      _checkAgentExistModel = value!;
      checkUserMessage.value = _checkAgentExistModel.message.success.first;
      isValidUser.value = true;
      update();
    }).catchError((onError) {
      checkUserMessage.value = Strings.notValidUser;
      isValidUser.value = false;
      log.e(onError);
    });

    _isCheckAgentLoading.value = false;
    update();
    return _checkAgentExistModel;
  }

  // ---------------------------------------------------------------------------
  //                             Check agent With Qr Code

  late CheckAgentWithQrCodeModel _checkAgentWithQrCodeModel;

  CheckAgentWithQrCodeModel get checkUserWithQrCodeModel =>
      _checkAgentWithQrCodeModel;
  Future<CheckAgentWithQrCodeModel> getCheckUserWithQrCodeData(
      String qrcode) async {
    _isCheckAgentLoading.value = true;

    Map<String, dynamic> inputBody = {'qr_code': qrcode};
    update();

    await ApiServices.checkAgentWithQrCodeApi(body: inputBody).then((value) {
      _checkAgentWithQrCodeModel = value!;
      copyInputController.clear();
      copyInputController.text = _checkAgentWithQrCodeModel.data.agentEmail;
      isValidUser.value = true;
      update();
    }).catchError((onError) {
      isValidUser.value = false;
      log.e(onError);
    });

    _isCheckAgentLoading.value = false;
    update();
    return _checkAgentWithQrCodeModel;
  }

  // ---------------------------------------------------------------------------
  //                             Check agent Exist

  final _isSendMoneyLoading = false.obs;

  bool get isSendMoneyLoading => _isSendMoneyLoading.value;
  late CommonSuccessModel _agentMoneyModel;
  CommonSuccessModel get agentMoneyModel => _agentMoneyModel;
  // ------------------------------API Function---------------------------------
  //
  Future<CommonSuccessModel> moneyOutProcess(context) async {
    _isSendMoneyLoading.value = true;

    Map<String, dynamic> inputBody = {
      'email': copyInputController.text,
      'amount': amountController.text,
    };
    update();

    await ApiServices.agentMoneyOutConfirmApi(body: inputBody).then((value) {
      _agentMoneyModel = value!;
      update();
    }).catchError((onError) {
      isValidUser.value = false;
      log.e(onError);
    });

    _isSendMoneyLoading.value = false;
    update();
    return _agentMoneyModel;
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
}
